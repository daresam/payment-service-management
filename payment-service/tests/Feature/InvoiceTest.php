<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    
    public function test_it_can_create_an_invoice_with_payment_terms()
    {
        $data = [
            'quantity' => 10,
            'rate' => 100.50,
            'issue_date' => '2025-05-21',
            'payment_terms' => 'Net 14',
            'description' => 'Test invoice',
        ];

        $response = $this->postJson('/api/corporate/1/vendor/1/invoice', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'corporate_id',
                'vendor_id',
                'invoice_number',
                'quantity',
                'rate',
                'amount',
                'status',
                'issue_date',
                'due_date',
                'payment_terms',
                'description',
            ])
            ->assertJsonFragment([
                'status' => 'OPEN',
                'amount' => '1005.00',
                'due_date' => '2025-06-04',
            ]);
    }

    public function test_it_fails_to_create_invoice_with_past_due_date()
    {
        $data = [
            'quantity' => 10,
            'rate' => 100.50,
            'issue_date' => '2024-01-01', // Past date to ensure due date is also in the past
            'payment_terms' => 'Net 14',
            'description' => 'Test invoice with past due date',
        ];

        $response = $this->postJson('/api/corporate/1/vendor/1/invoice', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_it_can_create_bulk_invoices_with_payment_terms()
    {
        $data = [
            'invoices' => [
                [
                    'vendor_id' => 1,
                    'quantity' => 10,
                    'rate' => 100.50,
                    'issue_date' => '2025-05-21',
                    'payment_terms' => 'Net 14',
                    'description' => 'Invoice 1',
                ],
                [
                    'vendor_id' => 2,
                    'quantity' => 5,
                    'rate' => 200.75,
                    'issue_date' => '2025-05-21',
                    'payment_terms' => 'Net 7',
                    'description' => 'Invoice 2',
                ],
            ],
        ];

        $response = $this->postJson('/api/corporate/1/invoices/bulk', $data);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'corporate_id',
                        'vendor_id',
                        'invoice_number',
                        'quantity',
                        'rate',
                        'amount',
                        'status',
                        'issue_date',
                        'due_date',
                        'payment_terms',
                        'description',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'corporate_id' => 1,
            'vendor_id' => 1,
            'amount' => 1005.00,
            'payment_terms' => 'Net 14',
        ]);
        $this->assertDatabaseHas('invoices', [
            'corporate_id' => 1,
            'vendor_id' => 2,
            'amount' => 1003.75,
            'payment_terms' => 'Net 7',
        ]);
    }

    public function test_it_can_list_invoices_for_a_vendor()
    {

        Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-01',
            'due_date' => '2025-05-08',
            'payment_terms' => 'Net 7',
        ]);
        Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->getJson('/api/corporate/1/vendor/1/invoice');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

    }

    public function test_it_can_filter_overdue_invoices()
    {

        Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-01',
            'due_date' => '2025-05-08',
            'payment_terms' => 'Net 7',
        ]);
        Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->getJson('/api/corporate/1/vendor/1/invoice?overdue=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['is_overdue' => true]);

    }

    public function test_it_can_show_a_specific_invoice()
    {
        $invoice = Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->getJson("/api/corporate/1/vendor/1/invoice/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $invoice->id]);
    }

    public function test_it_can_update_invoice_status_to_closed()
    {
        $invoice = Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->putJson("/api/corporate/1/vendor/1/invoice/{$invoice->id}", [
            'status' => 'CLOSED',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'CLOSED']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'CLOSED',
        ]);
    }

    public function test_it_fails_to_update_invoice_with_invalid_status()
    {
        $invoice = Invoice::factory()->create([
            'corporate_id' => 1,
            'vendor_id' => 1,
            'status' => 'OPEN',
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
        ]);

        $response = $this->putJson("/api/corporate/1/vendor/1/invoice/{$invoice->id}", [
            'status' => 'INVALID',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_it_prevents_reopening_closed_invoice()
    {

        $invoice = Invoice::factory()->create([
           'corporate_id' => 1,
            'vendor_id' => 1,
            'issue_date' => '2025-05-20',
            'due_date' => '2025-06-19',
            'payment_terms' => 'Net 30',
            'status' => 'CLOSED',
        ]);

        $response = $this->putJson("/api/corporate/1/vendor/1/invoice/{$invoice->id}", [
            'status' => 'OPEN',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Bad Request',
                'errors' => 'Cannot reopen a closed invoice',
            ]);
    }
}
