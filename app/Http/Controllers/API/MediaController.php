<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MediaController extends Controller
{
    protected $token;
    protected $baseUrl = 'https://query.ampre.ca/odata/Media';
    protected $cachePrefix = 'property_media:';
    protected $cacheDuration = 3600; // Cache for 1 hour

    public function __construct()
    {
        $this->token = env('TREB_DATA');
    }

    public function getPropertyMedia(Request $request)
    {
        try {
            $mlsNumber = $request->query('mlsNumber');
            if (empty($mlsNumber)) {
                Log::error('MLS number missing in request');
                return response()->json([
                    'success' => false,
                    'error' => 'MLS number is required'
                ], 400);
            }

            Log::info("Fetching media for MLS: {$mlsNumber}");

            $cacheKey = $this->cachePrefix . $mlsNumber;
            $response = Cache::remember($cacheKey, $this->cacheDuration, function () use ($mlsNumber) {
                $url = $this->baseUrl;
                $params = [
                    '$select' => 'MediaKey,MediaURL,ChangedByMemberID,ModificationTimestamp',
                    '$filter' => "ResourceRecordKey eq guid'$mlsNumber' and ResourceName eq 'Property'" // Try GUID format
                ];

                $queryUrl = $url . '?' . http_build_query($params);
                Log::info('Media request', [
                    'url' => $queryUrl,
                    'mlsNumber' => $mlsNumber,
                    'filter' => $params['$filter']
                ]);

                $response = Http::withOptions(['verify' => false])
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->token,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'OData-Version' => '4.0',
                        'Prefer' => 'odata.maxpagesize=100'
                    ])->get($queryUrl);

                if (!$response->successful()) {
                    Log::error('Media error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'mlsNumber' => $mlsNumber,
                        'query' => $queryUrl
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Failed to fetch property media',
                        'status' => $response->status(),
                        'details' => $response->body()
                    ];
                }

                $data = $response->json();
                if (isset($data['value']) && is_array($data['value'])) {
                    foreach ($data['value'] as &$media) {
                        if (isset($media['MediaKey'])) {
                            $media['ProxyURL'] = url('/api/media/proxy') . '?url=' . urlencode($this->baseUrl . "('" . $media['MediaKey'] . "')");
                        }
                    }
                }

                return [
                    'success' => true,
                    'value' => $data['value'] ?? []
                ];
            });

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Property media error', [
                'message' => $e->getMessage(),
                'mlsNumber' => $mlsNumber ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to get property media',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function proxyImage(Request $request)
    {
        try {
            $imageUrl = $request->query('url');
            if (!$imageUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Image URL is required'
                ], 400);
            }

            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'image/*'
                ])->get($imageUrl);

            if (!$response->successful()) {
                Log::error('Image proxy error', [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
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
                'success' => false,
                'error' => 'Failed to proxy image',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($mediaKey)
    {
        // Unchanged
        try {
            $url = $this->baseUrl . "('" . $mediaKey . "')";
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'OData-Version' => '4.0'
                ])->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch media',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], $response->status());
            }

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Media fetch error', [
                'mediaKey' => $mediaKey,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch media',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
