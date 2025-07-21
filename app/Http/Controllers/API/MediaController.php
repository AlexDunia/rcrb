<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    protected $token;
    protected $baseUrl = 'https://query.ampre.ca/odata/Media';

    public function __construct()
    {
        $this->token = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJ2ZW5kb3IvdHJyZWIvODk0MSIsImF1ZCI6IkFtcFVzZXJzUHJkIiwicm9sZXMiOlsiQW1wVmVuZG9yIl0sImlzcyI6InByb2QuYW1wcmUuY2EiLCJleHAiOjI1MzQwMjMwMDc5OSwiaWF0IjoxNzUxMDQzMjA4LCJzdWJqZWN0VHlwZSI6InZlbmRvciIsInN1YmplY3RLZXkiOiI4OTQxIiwianRpIjoiYjE3Y2RmZmQxMDE0MTlkYSIsImN1c3RvbWVyTmFtZSI6InRycmViIn0.jTM7dYhyLgYDPlDt4kF8VRs-rqIqmu4CvIR70L8cyZk';
    }

    /**
     * Get media by MediaKey
     */
    public function show($mediaKey)
    {
        try {
            $url = $this->baseUrl . "('" . $mediaKey . "')";

            $response = Http::withOptions([
                'verify' => false
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'OData-Version' => '4.0'
            ])->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch media',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], $response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Media fetch error', [
                'mediaKey' => $mediaKey,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch media',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get property media
     */
    public function getPropertyMedia(Request $request)
    {
        try {
            $mlsNumber = $request->query('mlsNumber');

            if (empty($mlsNumber)) {
                return response()->json([
                    'error' => 'MLS number is required'
                ], 400);
            }

            // Using the most basic query format from the docs
            $url = $this->baseUrl;

            // Simple filter without any boolean fields
            $params = [
                '$select' => 'MediaKey,MediaURL,ResourceRecordKey,ResourceName,ModificationTimestamp',
                '$filter' => sprintf("ResourceRecordKey eq '%s'", $mlsNumber)
            ];

            // Log the exact request we're making
            Log::info('Media request', [
                'url' => $url . '?' . http_build_query($params),
                'mlsNumber' => $mlsNumber
            ]);

            $response = Http::withOptions([
                'verify' => false
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'OData-Version' => '4.0',
                'Prefer' => 'odata.maxpagesize=100'
            ])->get($url . '?' . http_build_query($params));

            // Log the raw response for debugging
            Log::info('Raw response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('Media error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'error' => 'Failed to fetch property media',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Add proxy URLs to the response
            if (isset($data['value']) && is_array($data['value'])) {
                foreach ($data['value'] as &$media) {
                    if (isset($media['MediaKey'])) {
                        $media['ProxyURL'] = url('/api/media/proxy') . '?url=' . urlencode($this->baseUrl . "('" . $media['MediaKey'] . "')");
                    }
                }
            }

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Property media error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get property media',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proxy image requests to handle authentication
     */
    public function proxyImage(Request $request)
    {
        try {
            $imageUrl = $request->query('url');

            if (!$imageUrl) {
                return response()->json([
                    'error' => 'Image URL is required'
                ], 400);
            }

            $response = Http::withOptions([
                'verify' => false
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'image/*'
            ])->get($imageUrl);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch image',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], $response->status());
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';

            return response($response->body(), 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=31536000'
            ]);

        } catch (\Exception $e) {
            Log::error('Image proxy error', [
                'message' => $e->getMessage(),
                'url' => $request->query('url')
            ]);

            return response()->json([
                'error' => 'Failed to proxy image',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
