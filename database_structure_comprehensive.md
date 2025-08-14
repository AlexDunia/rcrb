# Real City Backend - Database Structure & Relationships

## Overview
This document provides a comprehensive walkthrough of the database structure and relationships in the Real City backend application. The system is designed as a real estate management platform with support for multiple property types, user management, media handling, and content management.

## Core Database Architecture

### 1. User Management System

#### Users Table (`users`)
The central user management table with authentication and agent capabilities.

**Fields:**
- `id` (Primary Key)
- `name` (string)
- `email` (string, unique)
- `password` (hashed)
- `role` (string)
- `phone` (string, nullable) - Added via migration
- `profile_photo_url` (string, nullable) - Added via migration
- `rating` (decimal 2,1, nullable) - Added via migration
- `email_verified_at` (timestamp, nullable)
- `remember_token` (string, nullable)
- Standard timestamps (`created_at`, `updated_at`)

**Model:** `App\Models\User`
- Uses Laravel Sanctum for API authentication
- Has API tokens, factory, and notification capabilities
- Password is automatically hashed
- Email verification support

### 2. Property Management System

#### Properties Table (`properties`)
The main properties table serving as the parent for all property types.

**Fields:**
- `id` (Primary Key)
- `agent_id` (Foreign Key → users) - Links to the agent who manages the property
- `name` (string)
- `description` (text)
- `price` (decimal 12,2)
- `address` (string)
- `city` (string)
- `state` (string)
- `zip_code` (string)
- `type` (string) - Property type classification
- `status` (string, default: 'for_sale')
- `bedrooms` (integer, nullable)
- `bathrooms` (decimal 3,1, nullable)
- `size` (decimal 10,2) - in square feet
- `year_built` (integer, nullable)
- `virtual_tour_url` (string, nullable)
- `neighborhood` (string, nullable)
- `latitude` (decimal 10,8, nullable)
- `longitude` (decimal 11,8, nullable)
- Standard timestamps (`created_at`, `updated_at`)
- `deleted_at` (soft delete)

**Model:** `App\Models\Property`
- Uses SoftDeletes trait
- Has proper data type casting for numeric fields
- Implements inheritance pattern for property types

#### Property Type Inheritance Tables

##### Residential Properties (`residential_properties`)
Extended details for residential properties.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `residential_type` (enum):
  - 'single_family'
  - 'apartment'
  - 'townhouse'
  - 'duplex'
  - 'condo'
  - 'villa'
- `bedrooms` (integer)
- `bathrooms` (integer)
- `total_rooms` (integer)
- `floor_area` (decimal 10,2) - in square feet
- `stories` (integer)
- `basement` (boolean, default: false)
- `garage` (boolean, default: false)
- `garage_size` (integer, nullable)
- Standard timestamps and soft deletes

**Model:** `App\Models\ResidentialProperty`
- Belongs to Property model

##### Commercial Properties (`commercial_properties`)
Extended details for commercial properties.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `commercial_type` (enum):
  - 'office'
  - 'retail'
  - 'industrial'
  - 'warehouse'
  - 'restaurant'
  - 'mixed_use'
- `total_area` (decimal 10,2) - in square feet
- `floors` (integer)
- `units` (integer)
- `loading_docks` (integer)
- `ceiling_height` (decimal 5,2) - in feet
- `zoning` (string)
- `current_use` (string)
- `potential_use` (string)
- Standard timestamps and soft deletes

**Model:** `App\Models\CommercialProperty`
- Belongs to Property model

##### Land Properties (`land_properties`)
Extended details for land properties.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `land_type` (enum):
  - 'residential_lot'
  - 'commercial_lot'
  - 'agricultural'
  - 'recreational'
  - 'industrial_lot'
- `topography` (string)
- `soil_type` (string)
- `utilities_available` (boolean)
- `road_frontage` (decimal 8,2) - in feet
- `zoning` (string)
- `current_use` (string)
- `potential_use` (string)
- Standard timestamps and soft deletes

**Model:** `App\Models\LandProperty`
- Belongs to Property model

### 3. Property Features & Amenities System

#### Features Table (`features`)
Property features like "Hardwood Floors", "Smart Home System", etc.

**Fields:**
- `id` (Primary Key)
- `name` (string)
- `category` (string, nullable)
- Standard timestamps and soft deletes

**Model:** `App\Models\Feature`
- Uses SoftDeletes trait
- Many-to-many relationship with properties

#### Amenities Table (`amenities`)
Property amenities like "Swimming Pool", "Fitness Center", etc.

**Fields:**
- `id` (Primary Key)
- `name` (string)
- `icon` (string, nullable)
- `description` (text, nullable)
- `category` (string, nullable)
- Standard timestamps and soft deletes

**Model:** `App\Models\Amenity`
- Many-to-many relationship with properties

#### Junction Tables

##### Property Features (`property_features`)
Links properties with their features.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `feature_id` (Foreign Key → features)
- Standard timestamps
- Unique constraint on `[property_id, feature_id]`

##### Property Amenities (`property_amenities`)
Links properties with their amenities.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `amenity_id` (Foreign Key → amenities)
- `details` (text, nullable) - Additional details about the amenity
- Standard timestamps and soft deletes
- Unique constraint on `[property_id, amenity_id]`

### 4. Media Management System

#### Property Media (`property_media`)
Handles all media assets related to properties.

**Fields:**
- `id` (Primary Key)
- `media_key` (UUID, unique) - Auto-generated UUID
- `property_id` (Foreign Key → properties)
- `resource_name` (string, default: 'Property') - Property, Office, Member
- `resource_record_key` (string) - Links to specific resource
- `media_type` (string) - image, video, virtual-tour, document
- `media_category` (string, nullable) - Primary, Secondary, etc.
- `image_size_description` (string, nullable) - Large, Medium, Small, etc.
- `media_url` (string)
- `media_caption` (string, nullable)
- `media_description` (text, nullable)
- `is_active` (boolean, default: true)
- `order_index` (integer, default: 0)
- `modification_timestamp` (timestamp) - Auto-updated
- Standard timestamps and soft deletes

**Indexes:**
- `resource_name`
- `resource_record_key`
- `media_type`
- `modification_timestamp`

**Model:** `App\Models\PropertyMedia`
- Uses SoftDeletes trait
- Auto-generates UUID for media_key
- Automatic URL handling for media files
- Active/Inactive state management
- Resource type filtering capabilities
- Media type categorization

### 5. Content Management System

#### Blog Posts (`allblogposts`)
Simple blog post management.

**Fields:**
- `id` (Primary Key)
- `title` (string)
- `content` (text)
- Standard timestamps

**Model:** `App\Models\Allblogpost`
- Uses HasFactory trait
- Simple content management

## Relationships Overview

### Property Model Relationships
```php
// One-to-One Relationships (Inheritance)
public function residential() {
    return $this->hasOne(ResidentialProperty::class);
}

public function commercial() {
    return $this->hasOne(CommercialProperty::class);
}

public function land() {
    return $this->hasOne(LandProperty::class);
}

// Many-to-Many Relationships
public function amenities() {
    return $this->belongsToMany(Amenity::class, 'property_amenities')
                ->withPivot('details')
                ->withTimestamps();
}

public function features() {
    return $this->belongsToMany(Feature::class, 'property_features')
                ->withTimestamps();
}

// One-to-Many Relationships
public function media() {
    return $this->hasMany(PropertyMedia::class);
}

// Belongs-To Relationships
public function agent() {
    return $this->belongsTo(User::class, 'agent_id');
}
```

### User Model Relationships
```php
// One-to-Many (as Agent)
// Properties managed by this user as an agent
// (Relationship defined in Property model)
```

### Property Type Models
```php
// All property type models have:
public function property() {
    return $this->belongsTo(Property::class);
}
```

### Feature & Amenity Models
```php
// Feature model:
public function properties() {
    return $this->belongsToMany(Property::class, 'property_features')
                ->withTimestamps();
}

// Amenity model:
public function properties() {
    return $this->belongsToMany(Property::class)->withPivot('details');
}
```

### PropertyMedia Model
```php
public function property() {
    return $this->belongsTo(Property::class);
}
```

## Database Design Patterns

### 1. Inheritance Pattern
The system uses a **Table Per Type (TPT)** inheritance pattern:
- Base `properties` table contains common fields
- Specific property types have their own tables with extended fields
- One-to-one relationships between base and specific tables
- Allows for different property types with specialized attributes

### 2. Many-to-Many Relationships
- **Features & Amenities**: Uses pivot tables with additional data
- **Property Features**: Simple many-to-many with timestamps
- **Property Amenities**: Many-to-many with additional `details` field

### 3. Media Management
- **Flexible Resource System**: Can handle media for properties, offices, members
- **UUID Tracking**: Each media item has a unique UUID for external system integration
- **Active/Inactive States**: Media can be temporarily disabled
- **Ordering System**: Media items can be ordered for display
- **Multiple Sizes**: Supports different image sizes and types

### 4. Soft Deletes
- Most tables implement soft deletes for data recovery
- Deleted records are marked with `deleted_at` timestamp
- Allows for data recovery and audit trails

### 5. Data Integrity
- **Foreign Key Constraints**: All relationships are properly constrained
- **Cascade Deletes**: When a property is deleted, all related records are removed
- **Unique Constraints**: Prevents duplicate relationships
- **Indexes**: Optimized for common queries

## API Integration Points

### TREB Integration
The system is designed to integrate with TREB (Toronto Real Estate Board) data:
- Media system supports external resource types
- Property data structure accommodates TREB listing formats
- UUID-based media tracking for external system synchronization

### Authentication System
- Laravel Sanctum for API authentication
- Personal access tokens for API access
- Role-based access control support

## Data Types and Validation

### Numeric Fields
- **Prices**: decimal(12,2) - Supports high-value properties
- **Coordinates**: decimal(10,8) for latitude, decimal(11,8) for longitude
- **Areas**: decimal(10,2) - in square feet
- **Bathrooms**: decimal(3,1) in properties table, integer in residential_properties
- **All room counts**: integer

### Boolean Fields
- `basement` (residential properties)
- `garage` (residential properties)
- `utilities_available` (land properties)
- `is_active` (media items)

### Special Fields
- **UUIDs**: For media tracking and external system integration
- **Enum types**: For property type classifications
- **Soft deletes**: On main entities for data recovery
- **Automatic timestamps**: On all main tables

## Performance Considerations

### Indexing Strategy
- Foreign keys are automatically indexed
- Media table has additional indexes for common queries
- Unique constraints provide additional indexing benefits

### Query Optimization
- Eager loading support for relationships
- Scope methods for common filters
- Soft delete scoping for active records

## Security Features

### Data Protection
- Password hashing for user accounts
- Soft deletes for data recovery
- Foreign key constraints for data integrity

### API Security
- Sanctum token authentication
- Role-based access control
- Request validation and sanitization

## Future Considerations

### Potential Enhancements
1. **Favorites System**: User property favorites/wishlist
2. **Search Optimization**: Full-text search capabilities
3. **Caching Layer**: Redis/Memcached for performance
4. **File Storage**: Cloud storage integration for media
5. **Audit Logging**: Comprehensive activity tracking
6. **Multi-tenancy**: Support for multiple real estate agencies

### Scalability Considerations
- Database indexing for large property datasets
- Media storage optimization for high-volume uploads
- API rate limiting for external integrations
- Caching strategies for frequently accessed data

This database structure provides a solid foundation for a real estate management system with room for expansion and customization based on specific business requirements. 
