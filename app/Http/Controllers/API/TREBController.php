<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TREBController extends Controller
{
    private $httpClient;

    public function __construct()
    {
        // Pre-configure HTTP client for better performance
        $this->httpClient = Http::withOptions([
            'verify' => false
        ])->withHeaders([
            'Authorization' => 'Bearer ' . config('services.treb.data'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'OData-Version' => '4.0'
        ]);
    }

    public function fetch()
    {
        try {
            $token = config('services.treb.data');
            Log::info('TREB API Token Debug', [
                'token_exists' => !empty($token),
                'token_length' => strlen($token ?? '')
            ]);

            $response = $this->httpClient->get('https://query.ampre.ca/odata/Property?$top=50&$orderby=ModificationTimestamp desc'); // Ordered by latest first

            Log::info('TREB API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

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

    public function fetchMedia($listingKey)
    {
        try {
            $token = config('services.treb.data');

            Log::info('TREB Media API Token Debug', [
                'token_exists' => !empty($token),
                'listingKey' => $listingKey
            ]);

            // Query to fetch up to 30 media items for the given listingKey
            $query = "\$filter=ResourceRecordKey eq '$listingKey' and ResourceName eq 'Property'&\$top=30&\$orderby=ModificationTimestamp,MediaKey";
            $encodedQuery = str_replace(' ', '%20', $query); // Basic URL encoding
            $url = "https://query.ampre.ca/odata/Media?$encodedQuery";

            $response = $this->httpClient->get($url);

            Log::info('TREB Media API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('TREB Media API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Media API request failed',
                    'status' => $response->status(),
                    'message' => $response->body()
                ], Response::HTTP_BAD_GATEWAY);
            }

            $data = $response->json();

            // Filter unique images by MediaObjectID, selecting LargestNoWatermark
            $mediaItems = $data['value'] ?? [];
            $uniqueMedia = [];
            $seenMediaObjectIDs = [];

            foreach ($mediaItems as $item) {
                $mediaObjectID = $item['MediaObjectID'];
                $imageSize = $item['ImageSizeDescription'];

                // Skip if we've already processed this MediaObjectID
                if (!in_array($mediaObjectID, $seenMediaObjectIDs)) {
                    // Prioritize LargestNoWatermark if available
                    if ($imageSize === 'LargestNoWatermark') {
                        $uniqueMedia[] = $item;
                        $seenMediaObjectIDs[] = $mediaObjectID;
                    }
                }
            }

            // If LargestNoWatermark is not found, fall back to other sizes
            foreach ($mediaItems as $item) {
                $mediaObjectID = $item['MediaObjectID'];
                $imageSize = $item['ImageSizeDescription'];

                if (!in_array($mediaObjectID, $seenMediaObjectIDs) && in_array($imageSize, ['Largest', 'Large', 'Medium', 'Thumbnail'])) {
                    $uniqueMedia[] = $item;
                    $seenMediaObjectIDs[] = $mediaObjectID;
                }
            }

            // Sort by Order to maintain display preference
            usort($uniqueMedia, function ($a, $b) {
                return $a['Order'] <=> $b['Order'];
            });

            // Rearrange to make 5th image first (if it exists)
            if (count($uniqueMedia) >= 5) {
                $fifthImage = array_splice($uniqueMedia, 4, 1)[0];
                array_unshift($uniqueMedia, $fifthImage);
            }

            return response()->json([
                'success' => true,
                'data' => ['value' => $uniqueMedia],
                'media_count' => count($uniqueMedia)
            ]);
        } catch (\Exception $e) {
            Log::error('TREB Media API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch TREB media',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchMembers()
    {
        try {
            $token = config('services.treb.data');

            Log::info('TREB Members API Token Debug', [
                'token_exists' => !empty($token),
                'token_length' => strlen($token ?? '')
            ]);

            $allMembers = [];
            $lastMemberKey = null;
            $batchSize = 1000;

            do {
                // Build OData query
                $query = "\$top=$batchSize&\$orderby=MemberKey";
                if ($lastMemberKey) {
                    $query .= "&\$filter=MemberKey gt '$lastMemberKey'";
                }
                $encodedQuery = str_replace(' ', '%20', $query);
                $url = "https://query.ampre.ca/odata/Member?$encodedQuery";

                $response = $this->httpClient->get($url);

                Log::info('TREB Members API Response', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                if (!$response->successful()) {
                    Log::error('TREB Members API Error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Members API request failed',
                        'status' => $response->status(),
                        'message' => $response->body()
                    ], Response::HTTP_BAD_GATEWAY);
                }

                $data = $response->json();
                $members = $data['value'] ?? [];

                $allMembers = array_merge($allMembers, $members);

                // Update lastMemberKey for the next batch
                $lastMemberKey = !empty($members) ? end($members)['MemberKey'] : null;

                Log::info('TREB Members Batch', [
                    'batch_count' => count($members),
                    'total_count' => count($allMembers),
                    'lastMemberKey' => $lastMemberKey
                ]);

            } while (count($members) === $batchSize);

            return response()->json([
                'success' => true,
                'data' => ['value' => $allMembers],
                'member_count' => count($allMembers)
            ]);

        } catch (\Exception $e) {
            Log::error('TREB Members API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch TREB members',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchSingleMember($memberKey)
    {
        try {
            $token = config('services.treb.data');

            Log::info('TREB Single Member API Token Debug', [
                'token_exists' => !empty($token),
                'memberKey' => $memberKey
            ]);

            $url = "https://query.ampre.ca/odata/Member('$memberKey')";

            $response = $this->httpClient->get($url);

            Log::info('TREB Single Member API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('TREB Single Member API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Single Member API request failed',
                    'status' => $response->status(),
                    'message' => $response->body()
                ], Response::HTTP_BAD_GATEWAY);
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('TREB Single Member API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch single TREB member',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function search(Request $request)
    {
        try {
            // Get search parameters from request
            $search = $request->input('q', $request->input('search')); // Check for 'q' first, fallback to 'search'
            $filter = $request->input('filter');
            $page = max(1, $request->input('page', 1));
            $perPage = min(50, max(1, $request->input('per_page', 10))); // Limit between 1-50
            $skip = ($page - 1) * $perPage;

            // Build OData query
            $query = [];

            // Add search filter if provided
            if ($search) {
                // Search in valid fields only
                $searchFields = [
                    "contains(UnparsedAddress, '$search')", // Street address
                    "contains(PostalCode, '$search')" // Postal code
                ];
                $query[] = '(' . implode(' or ', $searchFields) . ')';
            }

            // Add custom filters if provided
            if ($filter) {
                $query[] = "($filter)";
            }

            // Combine all query parts
            $filterQuery = $query ? '$filter=' . implode(' and ', $query) : '';

            // Add pagination
            $skipQuery = '$skip=' . $skip;
            $topQuery = '$top=' . $perPage;
            $countQuery = '$count=true';

            // Add ordering by newest first
            $orderQuery = '$orderby=ModificationTimestamp desc';

            // Build final query string
            $queryString = implode('&', array_filter([$filterQuery, $skipQuery, $topQuery, $countQuery, $orderQuery]));
            $url = 'https://query.ampre.ca/odata/Property?' . $queryString;

            Log::info('TREB Search API Request', [
                'url' => $url,
                'search' => $search,
                'filter' => $filter,
                'page' => $page,
                'perPage' => $perPage
            ]);

            $response = $this->httpClient->get($url);

            Log::info('TREB Search API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                Log::error('TREB Search API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Search API request failed',
                    'status' => $response->status(),
                    'message' => $response->body()
                ], Response::HTTP_BAD_GATEWAY);
            }

            $data = $response->json();

            // Extract total count from response
            $total = $data['@odata.count'] ?? 0;
            $lastPage = ceil($total / $perPage);

            $properties = $data['value'] ?? [];

            // Fetch media for all properties
            foreach ($properties as &$property) {
                if (isset($property['ListingKey'])) {
                    // Query media for this property
                    $mediaQuery = "\$filter=ResourceRecordKey eq '{$property['ListingKey']}' and ResourceName eq 'Property'&\$top=30&\$orderby=ModificationTimestamp,MediaKey";
                    $encodedQuery = str_replace(' ', '%20', $mediaQuery);
                    $mediaUrl = "https://query.ampre.ca/odata/Media?$encodedQuery";

                    $mediaResponse = $this->httpClient->get($mediaUrl);

                    if ($mediaResponse->successful()) {
                        $mediaData = $mediaResponse->json();
                        $mediaItems = $mediaData['value'] ?? [];
                        $uniqueMedia = [];
                        $seenMediaObjectIDs = [];

                        // First pass - get LargestNoWatermark images
                        foreach ($mediaItems as $item) {
                            $mediaObjectID = $item['MediaObjectID'];
                            $imageSize = $item['ImageSizeDescription'];

                            if (!in_array($mediaObjectID, $seenMediaObjectIDs)) {
                                if ($imageSize === 'LargestNoWatermark') {
                                    $uniqueMedia[] = $item;
                                    $seenMediaObjectIDs[] = $mediaObjectID;
                                }
                            }
                        }

                        // Second pass - get other sizes if needed
                        foreach ($mediaItems as $item) {
                            $mediaObjectID = $item['MediaObjectID'];
                            $imageSize = $item['ImageSizeDescription'];

                            if (!in_array($mediaObjectID, $seenMediaObjectIDs) &&
                                in_array($imageSize, ['Largest', 'Large', 'Medium', 'Thumbnail'])) {
                                $uniqueMedia[] = $item;
                                $seenMediaObjectIDs[] = $mediaObjectID;
                            }
                        }

                        // Sort by Order first
                        usort($uniqueMedia, function ($a, $b) {
                            return $a['Order'] <=> $b['Order'];
                        });

                        // Rearrange to make 5th image first (if it exists)
                        if (count($uniqueMedia) >= 5) {
                            $fifthImage = array_splice($uniqueMedia, 4, 1)[0];
                            array_unshift($uniqueMedia, $fifthImage);
                        }

                        $property['media'] = $uniqueMedia;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $properties,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => $skip + 1,
                    'to' => min($skip + $perPage, $total)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('TREB Search API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to search TREB listings',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
