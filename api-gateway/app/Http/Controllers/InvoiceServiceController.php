<?php

namespace App\Http\Controllers;

use App\Models\InterServiceTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InvoiceServiceController extends Controller
{
    public function index(Request $request, $corp_id, $vendor_id)
    {
        try {
            $tokens = $this->getTokens();

            if (!$tokens || empty($tokens->token)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            // Build query parameters safely
            $queryParams = array_filter([
                'status' => $request->input('status'),
                'due_date_from' => $request->input('due_date_from'),
                'due_date_to' => $request->input('due_date_to'),
                'overdue' => $request->input('overdue'),
            ], fn ($v) => ! is_null($v) && $v !== '');

            $url = env('PAYMENT_SERVICE_URL')."/corporate/{$corp_id}/vendor/{$vendor_id}/invoice";
            $response = Http::withHeaders([
                'Authorization' => "Bearer  $tokens->token",
            ])->get($url, $queryParams);

            $data = [
                'status' => 'success',
                'message' => 'Invoices fetched successfully',
                'data' => [
                    'invoices' => $response->json()['data'],
                ],
            ];

            return response()->json($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $corp_id, $vendor_id)
    {
        try {
            $tokens = $this->getTokens();
            if (!$tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->post(env('PAYMENT_SERVICE_URL').'/corporate/'.$corp_id.'/vendor/'.$vendor_id.'/invoice', $request->all());

            $data = [
                'status' => 'success',
                'message' => 'Invoice created successfully',
                'data' => [
                    'invoice' => $response->json()['data'],
                ],
            ];

            return response()->json($data, 201);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function bulkStore(Request $request, $corp_id)
    {
        try {
            $tokens = $this->getTokens();
            if (!$tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->post(env('PAYMENT_SERVICE_URL').'/corporate/'.$corp_id.'/invoices/bulk', $request->all());

            $data = [
                'status' => 'success',
                'message' => 'Invoice created successfully',
                'data' => [
                    'invoice' => $response->json()['data'],
                ],
            ];

            return response()->json($data, 201);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $corp_id, $vendor_id, $invoice_id)
    {
        try {
            $tokens = $this->getTokens();
            if (!$tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->put(env('PAYMENT_SERVICE_URL').'/corporate/'.$corp_id.'/vendor/'.$vendor_id.'/invoice/'.$invoice_id, $request->all());


            $dataResponse = $response->json();
            if (isset($dataResponse['status']) && $dataResponse['status'] === 'error') {
                return response()->json($dataResponse, 400);
            }

            $dataResponse = [
                'status' => 'success',
                'message' => 'Invoice updated successfully',
                'data' => [
                    'invoice' => $response->json()['data'],
                ],
            ];

            return response()->json($dataResponse);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function show($corp_id, $vendor_id, $invoice_id)
    {
        try {
            $tokens = $this->getTokens();
            if (! $tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->get(env('PAYMENT_SERVICE_URL').'/corporate/'.$corp_id.'/vendor/'.$vendor_id.'/invoice/'.$invoice_id);

            $data = [
                'status' => 'success',
                'message' => 'Invoice fetched successfully',
                'data' => [
                    'invoice' => $response->json()['data'],
                ],
            ];

            return response()->json($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    private function getTokens()
    {
        $tokens = Cache::get('inter_service_token_invoice');
        if (empty($tokens)) {
            $tokens = InterServiceTokens::where('issuer_service_id', env('PAYMENT_SERVICE_ID'))->get()->first();
            if (empty($tokens) || $tokens->api_token_expires_at->isPast()) {
                $request = Http::post(env('PAYMENT_SERVICE_URL').'/service-accounts/token', [
                    'service_id' => env('API_GATEWAY_SERVICE_ID'),
                    'service_secret' => env('API_GATEWAY_SERVICE_SECRET'),
                ]);

                $response = $request->json();

                if (! isset($response['data']['token'])) {
                    return [];
                }

                $tokens = InterServiceTokens::updateOrCreate(
                    ['issuer_service_id' => env('PAYMENT_SERVICE_ID')],
                    [
                        'token' => $response['data']['token'],
                        // convert the expires_in to a timestamp to store in the database
                        'api_token_expires_at' => $response['data']['expires_at'],
                    ]
                );

                // Cache the tokens with the time left before it expires
                Cache::put('inter_service_token_invoice', $tokens, now()->diffInSeconds($response['data']['expires_at']));
            }
        }

        return $tokens;
    }
}
