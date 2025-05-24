<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{

    public function store(Request $request, $corp_id, $vendor_id)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'rate' => 'required|numeric|min:0.01',
            'issue_date' => 'required|date|before_or_equal:today',
            'payment_terms' => 'required|in:Net 7,Net 14,Net 30',
            'description' => 'nullable|string|max:1000',
        ]);

        $vendor = Vendor::where('corporate_id', $corp_id)->findOrFail($vendor_id);

        // Calculate due date based on issue date and payment terms
        $dueDate = Carbon::parse($validated['issue_date'])->addDays(
            match ($validated['payment_terms']) {
                'Net 7' => 7,
                'Net 14' => 14,
                'Net 30' => 30,
            }
        );

        // Validate that due date is not in the past
        if ($dueDate->isPast()) {
            throw ValidationException::withMessages([
                'due_date' => ['The calculated due date cannot be in the past.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $invoice = Invoice::create([
                'corporate_id' => $corp_id,
                'vendor_id' => $vendor_id,
                'invoice_number' => 'INV-'.Str::random(8),
                'quantity' => $validated['quantity'],
                'rate' => $validated['rate'],
                'amount' => $validated['quantity'] * $validated['rate'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $dueDate,
                'payment_terms' => $validated['payment_terms'],
                'description' => $validated['description'],
                'status' => 'OPEN',
            ]);
            Cache::forget("corporate_{$corp_id}_vendor_{$vendor_id}_invoices");
            Log::info('Invoice created', ['id' => $invoice->id]);
            DB::commit();

            return response()->json($invoice, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice creation failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create invoice'], 500);
        }
    }

    public function bulkStore(Request $request, $corp_id)
    {

        $validated = $request->validate([
            'invoices' => 'required|array|min:1',
            'invoices.*.vendor_id' => 'required',
            'invoices.*.quantity' => 'required|integer|min:1',
            'invoices.*.rate' => 'required|numeric|min:0.01',
            'invoices.*.issue_date' => 'required|date|before_or_equal:today',
            'invoices.*.payment_terms' => 'required|in:Net 7,Net 14,Net 30',
            'invoices.*.description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            $createdInvoices = [];
            foreach ($validated['invoices'] as $invoiceData) {
                $dueDate = Carbon::parse($invoiceData['issue_date'])->addDays(
                    match ($invoiceData['payment_terms']) {
                        'Net 7' => 7,
                        'Net 14' => 14,
                        'Net 30' => 30,
                    }
                );

                $invoice = Invoice::create([
                    'corporate_id' => $corp_id,
                    'vendor_id' => $invoiceData['vendor_id'],
                    'invoice_number' => 'INV-'.Str::random(8),
                    'quantity' => $invoiceData['quantity'],
                    'rate' => $invoiceData['rate'],
                    'amount' => $invoiceData['quantity'] * $invoiceData['rate'],
                    'issue_date' => $invoiceData['issue_date'],
                    'due_date' => $dueDate,
                    'payment_terms' => $invoiceData['payment_terms'],
                    'description' => $invoiceData['description'] ?? null,
                    'status' => 'OPEN',
                ]);
                Cache::forget("corporate_{$corp_id}_vendor_{$invoiceData['vendor_id']}_invoices");
                $createdInvoices[] = $invoice;
            }
            Log::info('Bulk invoices created', ['count' => count($createdInvoices)]);
            DB::commit();

            return response()->json(['data' => $createdInvoices], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk invoice creation failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create invoices'], 500);
        }
    }

    public function index(Request $request, $corp_id, $vendor_id)
    {

        $validated = $request->validate([
            'status' => 'nullable|in:OPEN,CLOSED',
            'due_date_from' => 'nullable|date',
            'due_date_to' => 'nullable|date|after_or_equal:due_date_from',
            'overdue' => 'nullable',
        ]);

        // Convert overdue to boolean
        $validated['overdue'] = filter_var($validated['overdue'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $query = Invoice::where('corporate_id', $corp_id)
                ->where('vendor_id', $vendor_id);

            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (isset($validated['due_date_from'])) {
                $query->whereDate('due_date', '>=', $validated['due_date_from']);
            }
            if (isset($validated['due_date_to'])) {
                $query->whereDate('due_date', '<=', $validated['due_date_to']);
            }
            if (isset($validated['overdue']) && $validated['overdue']) {
                $query->where('status', 'OPEN')->where('due_date', '<', now());
            }

            $invoices = $query->latest()->get()->map(function ($invoice) {
                return array_merge($invoice->toArray(), ['is_overdue' => $invoice->isOverdue()]);
            })->toArray();

            return response()->json(['data' => $invoices]);
        } catch (\Exception $e) {
            Log::error('Invoice fetch failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to fetch invoices'], 500);
        }
    }

    public function show($corp_id, $vendor_id, $invoice_id)
    {

        try {
            $invoice_i = Cache::remember("invoice_{$invoice_id}", 3600, function () use ($invoice_id, $corp_id, $vendor_id) {
                return Invoice::where('corporate_id', $corp_id)
                    ->where('vendor_id', $vendor_id)
                    ->findOrFail($invoice_id);
            });

            return response()->json(['data' => array_merge($invoice_i->toArray(), ['is_overdue' => $invoice_i->isOverdue()])]);
        } catch (\Exception $e) {
            Log::error('Invoice fetch failed', ['id' => $invoice_id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Invoice not found'], 404);
        }
    }

    public function update(Request $request, $corp_id, $vendor_id, $invoice_id)
    {

        $validated = $request->validate([
            'status' => 'required|in:OPEN,CLOSED',
        ]);

        try {
            DB::beginTransaction();
            $invoice = Invoice::where('corporate_id', $corp_id)
                ->where('vendor_id', $vendor_id)
                ->findOrFail($invoice_id);

            if ($invoice->status === 'CLOSED' && $validated['status'] === 'OPEN') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bad Request',
                    'errors' => 'Cannot reopen a closed invoice',
                ], 400);
            }

            $invoice->update(['status' => $validated['status']]);
            Cache::forget("corporate_{$corp_id}_vendor_{$vendor_id}_invoices");
            Cache::forget("invoice_{$invoice_id}");
            Log::info('Invoice updated', ['id' => $invoice_id]);
            DB::commit();

            return response()->json(['data' => $invoice]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update failed', ['id' => $invoice_id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to update invoice'], 500);
        }
    }
}
