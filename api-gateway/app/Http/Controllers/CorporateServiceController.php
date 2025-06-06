<?php

namespace App\Http\Controllers;

use App\Models\InterServiceTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CorporateServiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $tokens = $this->getTokens();

            if (! $tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            // $userId = Auth::user()->id;
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->get(env('CORPORATE_SERVICE_URL').'/corporate');

            $data = [
                'status' => 'success',
                'message' => 'Corporates fetched successfully',
                'data' => [
                    'corporates' => $response->json(),
                ],
            ];

            return response()->json($data);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    public function show($id)
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
            ])->get(env('CORPORATE_SERVICE_URL').'/corporate/'.$id);

            $data = [
                'status' => 'success',
                'message' => 'Corporate fetched successfully',
                'data' => [
                    'corporate' => $response->json(),
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
            if (! $tokens) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get tokens',
                ], 500);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->getTokens()->token,
            ])->post(env('CORPORATE_SERVICE_URL').'/corporate', $request->all());

            $data = [
                'status' => 'success',
                'message' => 'Corporate created successfully',
                'data' => [
                    'corporates' => $response->json(),
                ],
            ];

            return response()->json($data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json(['error' => 'Failed to connect', 'exception' => $e->getMessage()], 500);
        }
    }

    private function getTokens()
    {
        $tokens = Cache::get('inter_service_token_corporate');
        if (empty($tokens)) {
            $tokens = InterServiceTokens::where('issuer_service_id', env('CORPORATE_SERVICE_ID'))->get()->first();
            if (empty($tokens) || $tokens->api_token_expires_at->isPast()) {
                $request = Http::post(env('CORPORATE_SERVICE_URL').'/service-accounts/token', [
                    'service_id' => env('API_GATEWAY_SERVICE_ID'),
                    'service_secret' => env('API_GATEWAY_SERVICE_SECRET'),
                ]);

                $response = $request->json();

                if (! isset($response['data']['token'])) {
                    return [];
                }

                $tokens = InterServiceTokens::updateOrCreate(
                    ['issuer_service_id' => env('CORPORATE_SERVICE_ID')],
                    [
                        'token' => $response['data']['token'],
                        // convert the expires_in to a timestamp to store in the database
                        'api_token_expires_at' => $response['data']['expires_at'],
                    ]
                );

                // Cache the tokens with the time left before it expires
                Cache::put('inter_service_token_corporate', $tokens, now()->diffInSeconds($response['data']['expires_at']));
            }
        }

        return $tokens;
    }
}
