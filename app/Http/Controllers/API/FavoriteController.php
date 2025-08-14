<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FavoritesController extends Controller
{
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'property_id' => 'required|string|max:255',
                'property_type' => 'required|in:local,treb',
                'details' => 'nullable|array',
            ]);

            $user = $request->user();
            if (!$user) {
                Log::error('User not authenticated', ['request' => $request->all()]);
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $existing = Favorite::where('user_id', $user->id)
                ->where('property_id', $request->property_id)
                ->where('property_type', $request->property_type)
                ->first();

            if ($existing) {
                $existing->delete();
                return response()->json(['message' => 'Favorite removed']);
            }

            Favorite::create([
                'user_id' => $user->id,
                'property_id' => $request->property_id,
                'property_type' => $request->property_type,
                'details' => $request->details,
            ]);

            return response()->json(['message' => 'Favorite added']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in FavoritesController', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Toggle favorite failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to toggle favorite', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $favorites = Favorite::where('user_id', $request->user()->id)
                ->withTrashed()
                ->get();
            return response()->json(['favorites' => $favorites]);
        } catch (\Exception $e) {
            Log::error('Fetch favorites failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch favorites'], 500);
        }
    }
}
