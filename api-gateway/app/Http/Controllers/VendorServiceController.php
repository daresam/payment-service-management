<?php

namespace App\Http\Controllers;

use App\Models\InterServiceTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VendorServiceController extends Controller
{
    public function index(Request $request)
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
            ])->get(env('VENDOR_SERVICE_URL').'/corporate/vendor');

            $data = [
                'status' => 'success',
                'message' => 'Vendors fetched successfully',
                'data' => [
                    'vendors' => $response->json(),
                ],
            ];

            return response()->json($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
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
                'Authorization' => 'Bearer ' . $this->getTokens()->token,
            ])->put(env('VENDOR_SERVICE_URL') . '/corporate/vendor/' . $id, $request->all());


            $data = [
                'status' => 'success',
                'message' => 'Vendor updated successfully',
                'data' => [
                    'vendors' => $response->json(),
                ],
            ];

            return response()->json($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
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
            ])->post(env('VENDOR_SERVICE_URL').'/corporate/vendor', $request->all());

            $data = [
                'status' => 'success',
                'message' => 'Vendor created successfully',
                'data' => [
                    'vendors' => $response->json(),
                ],
            ];

            return response()->json($data, 201);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    private function getTokens()
    {
        $tokens = Cache::get('inter_service_token_vendor');
        if (empty($tokens)) {
            $tokens = InterServiceTokens::where('issuer_service_id', env('VENDOR_SERVICE_ID'))->get()->first();
            if (empty($tokens) || $tokens->api_token_expires_at->isPast()) {
                $request = Http::post(env('VENDOR_SERVICE_URL').'/service-accounts/token', [
                    'service_id' => env('API_GATEWAY_SERVICE_ID'),
                    'service_secret' => env('API_GATEWAY_SERVICE_SECRET'),
                ]);

                $response = $request->json();

                if (! isset($response['data']['token'])) {
                    return [];
                }

                $tokens = InterServiceTokens::updateOrCreate(
                    ['issuer_service_id' => env('VENDOR_SERVICE_ID')],
                    [
                        'token' => $response['data']['token'],
                        // convert the expires_in to a timestamp to store in the database
                        'api_token_expires_at' => $response['data']['expires_at'],
                    ]
                );

                // Cache the tokens with the time left before it expires
                Cache::put('inter_service_token_vendor', $tokens, now()->diffInSeconds($response['data']['expires_at']));
            }
        }

        return $tokens;
    }
}
