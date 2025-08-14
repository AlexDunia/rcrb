# Favorites System - Complete Implementation Guide

## Overview

The favorites system allows authenticated users to save and manage their favorite properties from both local database and TREB (Toronto Regional Real Estate Board) sources. The system implements a toggle functionality where users can add or remove favorites with a single API call.

## Architecture Components

### 1. Database Schema

#### Favorites Table Structure
```sql
CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    property_id VARCHAR(255) NOT NULL,
    property_type ENUM('local', 'treb') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_property (user_id, property_id, property_type)
);
```

#### Key Design Decisions:
- **`property_id` as VARCHAR**: Supports both numeric local IDs and alphanumeric TREB ListingKeys (e.g., "X12293106")
- **`property_type` ENUM**: Distinguishes between 'local' (properties table) and 'treb' (TREB API) sources
- **Soft Deletes**: Maintains data integrity and allows for potential recovery
- **Unique Constraint**: Prevents duplicate favorites for the same user-property combination
- **Cascade Delete**: Automatically removes favorites when a user is deleted

### 2. Eloquent Models

#### Favorite Model (`app/Models/Favorite.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'property_id',
        'property_type',
    ];

    protected $casts = [
        'property_id' => 'string',
    ];

    /**
     * Get the user that owns the favorite.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

#### User Model Updates (`app/Models/User.php`)
```php
// Added import
use Illuminate\Database\Eloquent\Relations\HasMany;

// Added method
public function favorites(): HasMany
{
    return $this->hasMany(Favorite::class);
}
```

### 3. API Controller

#### FavoriteController (`app/Http/Controllers/API/FavoriteController.php`)

The controller implements two main methods:

##### `index()` Method - Get User Favorites
```php
public function index(): JsonResponse
{
    $user = Auth::user();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    $favorites = $user->favorites()->select('id', 'property_id', 'property_type', 'created_at')->get();

    return response()->json([
        'success' => true,
        'data' => $favorites
    ]);
}
```

##### `storefav()` Method - Toggle Favorite
```php
public function storefav(Request $request): JsonResponse
{
    // Validate the request
    $validated = $request->validate([
        'property_id' => 'required|string',
        'property_type' => ['required', Rule::in(['local', 'treb'])],
    ]);

    $user = Auth::user();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated'
        ], 401);
    }

    // Check if the favorite already exists
    $existingFavorite = Favorite::where([
        'user_id' => $user->id,
        'property_id' => $validated['property_id'],
        'property_type' => $validated['property_type'],
    ])->first();

    if ($existingFavorite) {
        // Remove the favorite (soft delete)
        $existingFavorite->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Property removed from favorites',
            'action' => 'removed',
            'data' => [
                'property_id' => $validated['property_id'],
                'property_type' => $validated['property_type'],
            ]
        ]);
    } else {
        // Create a new favorite
        $favorite = Favorite::create([
            'user_id' => $user->id,
            'property_id' => $validated['property_id'],
            'property_type' => $validated['property_type'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Property added to favorites',
            'action' => 'added',
            'data' => [
                'id' => $favorite->id,
                'property_id' => $favorite->property_id,
                'property_type' => $favorite->property_type,
                'created_at' => $favorite->created_at,
            ]
        ]);
    }
}
```

### 4. API Routes

#### Route Configuration (`routes/api.php`)
```php
// Favorites routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'storefav']);
});
```

Both routes are protected by Laravel Sanctum authentication middleware.

## API Documentation

### Authentication
All favorites endpoints require authentication via Laravel Sanctum. Include the bearer token in the Authorization header:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Endpoints

#### GET /api/favorites
**Description**: Retrieve all favorites for the authenticated user

**Response Format**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "property_id": "123",
      "property_type": "local",
      "created_at": "2025-08-06T19:33:12.000000Z"
    },
    {
      "id": 2,
      "property_id": "X12293106",
      "property_type": "treb",
      "created_at": "2025-08-06T19:35:45.000000Z"
    }
  ]
}
```

#### POST /api/favorites
**Description**: Toggle favorite status for a property (add if not exists, remove if exists)

**Request Format**:
```json
{
  "property_id": "X12293106",
  "property_type": "treb"
}
```

**Validation Rules**:
- `property_id`: required, string
- `property_type`: required, must be either 'local' or 'treb'

**Response Format (Added)**:
```json
{
  "success": true,
  "message": "Property added to favorites",
  "action": "added",
  "data": {
    "id": 1,
    "property_id": "X12293106",
    "property_type": "treb",
    "created_at": "2025-08-06T19:33:12.000000Z"
  }
}
```

**Response Format (Removed)**:
```json
{
  "success": true,
  "message": "Property removed from favorites",
  "action": "removed",
  "data": {
    "property_id": "X12293106",
    "property_type": "treb"
  }
}
```

## Usage Examples

### Frontend JavaScript Integration
```javascript
// Add/Remove favorite
async function toggleFavorite(propertyId, propertyType) {
    try {
        const response = await fetch('/api/favorites', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${userToken}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                property_id: propertyId,
                property_type: propertyType
            })
        });

        const result = await response.json();
        
        if (result.success) {
            console.log(`Property ${result.action}:`, result.data);
            // Update UI based on result.action ('added' or 'removed')
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

// Get user favorites
async function getUserFavorites() {
    try {
        const response = await fetch('/api/favorites', {
            headers: {
                'Authorization': `Bearer ${userToken}`,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        
        if (result.success) {
            return result.data;
        }
    } catch (error) {
        console.error('Error fetching favorites:', error);
    }
}
```

### cURL Examples

**Add TREB Property to Favorites**:
```bash
curl -X POST http://your-domain.com/api/favorites \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"property_id": "X12293106", "property_type": "treb"}'
```

**Add Local Property to Favorites**:
```bash
curl -X POST http://your-domain.com/api/favorites \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"property_id": "123", "property_type": "local"}'
```

**Get User Favorites**:
```bash
curl -X GET http://your-domain.com/api/favorites \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Implementation Features

### ✅ Core Requirements Met
- **Toggle Functionality**: Single endpoint handles both add and remove operations
- **Dual Property Support**: Handles both local properties and TREB properties
- **String Property IDs**: Supports alphanumeric TREB ListingKeys
- **Authentication**: Secured with Laravel Sanctum
- **Validation**: Proper request validation with clear error messages

### ✅ Additional Features
- **Soft Deletes**: Data preservation for potential recovery
- **Unique Constraints**: Prevents duplicate favorites
- **Relationship Models**: Proper Eloquent relationships
- **Comprehensive API**: Both toggle and retrieve endpoints
- **Error Handling**: Proper HTTP status codes and error messages
- **Type Safety**: Proper type hints and return types

### ✅ Security Considerations
- **Authentication Required**: All endpoints protected by Sanctum middleware
- **User Isolation**: Users can only access their own favorites
- **Input Validation**: Strict validation rules prevent invalid data
- **SQL Injection Protection**: Using Eloquent ORM prevents SQL injection

## Database Migration Commands

To implement this system in your environment:

```bash
# Create and run the migration
php artisan make:migration create_favorites_table
php artisan migrate

# Create the model and controller (already done)
php artisan make:model Favorite
php artisan make:controller API/FavoriteController
```

## Testing the Implementation

### Verify Routes
```bash
php artisan route:list --path=favorites
```

Expected output:
```
GET|HEAD   api/favorites ............................................... API\FavoriteController@index
POST       api/favorites ............................................ API\FavoriteController@storefav
```

### Test Database Connection
```bash
php artisan tinker
```

```php
// In tinker
use App\Models\User;
use App\Models\Favorite;

// Create a test favorite
$user = User::first();
$favorite = Favorite::create([
    'user_id' => $user->id,
    'property_id' => 'TEST123',
    'property_type' => 'local'
]);

// Verify relationship
$user->favorites; // Should show the favorite
$favorite->user;  // Should show the user
```

## Future Enhancements

### Potential Extensions
1. **Favorites Sharing**: Allow users to share favorite lists
2. **Favorites Categories**: Organize favorites into custom categories
3. **Favorites Notes**: Add personal notes to favorites
4. **Bulk Operations**: Add/remove multiple favorites at once
5. **Favorites Export**: Export favorites to PDF or CSV
6. **Favorites Analytics**: Track popular properties across users

### Performance Optimizations
1. **Database Indexing**: Add indexes on frequently queried columns
2. **Caching**: Cache user favorites for faster retrieval
3. **Pagination**: Implement pagination for users with many favorites
4. **Eager Loading**: Optimize queries with eager loading when needed

## Troubleshooting

### Common Issues

**Migration Errors**:
- Ensure users table exists before running favorites migration
- Check database connection settings

**Authentication Issues**:
- Verify Sanctum is properly configured
- Ensure bearer token is valid and not expired

**Validation Errors**:
- Check property_type is exactly 'local' or 'treb'
- Ensure property_id is provided as a string

**Duplicate Entry Errors**:
- The unique constraint prevents duplicates - this is expected behavior
- Use the toggle endpoint which handles this automatically

This completes the comprehensive favorites system implementation with full documentation and usage examples.
