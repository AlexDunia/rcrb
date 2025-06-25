<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['features', 'amenities', 'media', 'agent']);

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('minPrice')) {
            $query->where('price', '>=', $request->minPrice);
        }

        if ($request->has('maxPrice')) {
            $query->where('price', '<=', $request->maxPrice);
        }

        if ($request->has('bedrooms')) {
            $query->where('bedrooms', '>=', $request->bedrooms);
        }

        if ($request->has('bathrooms')) {
            $query->where('bathrooms', '>=', $request->bathrooms);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Add pagination
        $properties = $query->paginate($request->input('per_page', 12));

        return PropertyResource::collection($properties);
    }

    public function featured()
    {
        $properties = Property::with(['features', 'amenities', 'media', 'agent'])
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return PropertyResource::collection($properties);
    }

    public function show($id)
    {
        $property = Property::with(['features', 'amenities', 'media', 'agent'])
            ->findOrFail($id);

        return new PropertyResource($property);
    }
}
