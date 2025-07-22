<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TREBController extends Controller
{
    public function fetch()
    {
        try {
            $token = config('services.treb.data');

            // Add debug logging
            Log::info('TREB API Token Debug', [
                'token_exists' => !empty($token),
                'token_length' => strlen($token ?? '')
            ]);

            $response = Http::withOptions([
                'verify' => false // 🚨 disables SSL verification - for development only
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'OData-Version' => '4.0'
            ])->get('https://query.ampre.ca/odata/Property?$top=5');

            // Log the response for debugging
            Log::info('TREB API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Check if the response is successful
            if (!$response->successful()) {
                Log::error('TREB API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'API request failed',
                    'status' => $response->status(),
                    'message' => $response->body()
                ], Response::HTTP_BAD_GATEWAY);
            }

            // Try to decode the JSON response
            $data = $response->json();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('TREB API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch TREB listings',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
