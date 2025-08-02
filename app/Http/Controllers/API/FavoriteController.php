<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    /**
     * Toggle favorite status for a property
     */
    public function toggle(Request $request)
    {
        try {
            $validated = $request->validate([
                'property_type' => 'required|in:local,treb',
                'property_id' => 'required|string',
                'property_data' => 'nullable|array' // For TREB properties
            ]);

            $user = Auth::user();

            // Check if already favorited
            $existing = Favorite::where([
                'user_id' => $user->id,
                'property_type' => $validated['property_type'],
                'property_id' => $validated['property_id']
            ])->first();

            if ($existing) {
                // Remove from favorites
                $existing->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Removed from favorites',
                    'is_favorited' => false
                ]);
            }

            // For local properties, verify the property exists
            if ($validated['property_type'] === 'local') {
                $property = Property::find($validated['property_id']);
                if (!$property) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Local property not found'
                    ], 404);
                }
            }

            // Add to favorites
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->property_type = $validated['property_type'];
            $favorite->property_id = $validated['property_id'];

            // If it's a TREB property, store the property data
            if ($validated['property_type'] === 'treb') {
                $favorite->property_data = $validated['property_data'];
            }

            $favorite->save();

            return response()->json([
                'success' => true,
                'message' => 'Added to favorites',
                'is_favorited' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Favorite toggle failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's favorites
     */
    public function index()
    {
        try {
            $user = Auth::user();

            $favorites = Favorite::where('user_id', $user->id)
                ->get()
                ->map(function ($favorite) {
                    if ($favorite->property_type === 'local') {
                        // Get fresh data for local properties
                        $property = Property::find($favorite->property_id);
                        return [
                            'id' => $favorite->id,
                            'property_type' => 'local',
                            'property' => $property,
                            'created_at' => $favorite->created_at
                        ];
                    } else {
                        // Use stored data for TREB properties
                        return [
                            'id' => $favorite->id,
                            'property_type' => 'treb',
                            'property' => $favorite->property_data,
                            'created_at' => $favorite->created_at
                        ];
                    }
                });

            return response()->json([
                'success' => true,
                'favorites' => $favorites
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch favorites failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch favorites',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a property is favorited
     */
    public function check(Request $request)
    {
        try {
            $validated = $request->validate([
                'property_type' => 'required|in:local,treb',
                'property_id' => 'required|string'
            ]);

            $user = Auth::user();

            $isFavorited = Favorite::where([
                'user_id' => $user->id,
                'property_type' => $validated['property_type'],
                'property_id' => $validated['property_id']
            ])->exists();

            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited
            ]);
        } catch (\Exception $e) {
            Log::error('Check favorite status failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
