# Real City Database Structure

## Core Tables

### Properties (`properties` table)
The main table storing basic property information.

**Fields:**
- `id` (Primary Key)
- `agent_id` (Foreign Key → users)
- `name` (string)
- `description` (text)
- `price` (decimal, 12,2)
- `address` (string)
- `city` (string)
- `state` (string)
- `zip_code` (string)
- `type` (string)
- `status` (string, default: 'for_sale')
- `bedrooms` (integer, nullable)
- `bathrooms` (decimal, 3,1, nullable)
- `size` (decimal, 10,2) - in square feet
- `year_built` (integer, nullable)
- `virtual_tour_url` (string, nullable)
- `neighborhood` (string, nullable)
- `latitude` (decimal, 10,8, nullable)
- `longitude` (decimal, 11,8, nullable)
- Standard timestamps (`created_at`, `updated_at`, `deleted_at`)

### Residential Properties (`residential_properties` table)
Extended details for residential properties.

**Fields:**
- `id` (Primary Key)
- `property_id` (Foreign Key → properties)
- `residential_type` (enum)
  - Allowed values: 
    - 'single_family'
    - 'apartment'
    - 'townhouse'
    - 'duplex'
    - 'condo'
    - 'villa'
- `bedrooms` (integer)
- `bathrooms` (integer)
- `total_rooms` (integer)
- `floor_area` (decimal, 10,2) - in square feet
- `stories` (integer)
- `basement` (boolean, default: false)
- `garage` (boolean, default: false)
- `garage_size` (integer, nullable)
- Standard timestamps (`created_at`, `updated_at`, `deleted_at`)

### Property Media (`property_media` table)
Handles all media (images, videos, etc.) associated with properties.

**Fields:**
- `id` (Primary Key)
- `media_key` (UUID, unique)
- `property_id` (Foreign Key → properties)
- `resource_name` (string, default: 'Property')
- `resource_record_key` (string)
- `media_type` (string) - e.g., 'image', 'video', 'virtual-tour', 'document'
- `media_category` (string, nullable) - e.g., 'Primary', 'Secondary'
- `image_size_description` (string, nullable) - e.g., 'Large', 'Medium', 'Small'
- `media_url` (string)
- `media_caption` (string, nullable)
- `media_description` (text, nullable)
- `is_active` (boolean, default: true)
- `order_index` (integer, default: 0)
- `modification_timestamp` (timestamp)
- Standard timestamps (`created_at`, `updated_at`, `deleted_at`)

**Indexes:**
- `resource_name`
- `resource_record_key`
- `media_type`
- `modification_timestamp`

### Features and Amenities

#### Features (`features` table)
Property features like "Hardwood Floors", "Smart Home System", etc.

**Common Features:**
- Hardwood Floors
- Granite Countertops
- Floor-to-ceiling Windows
- Smart Home System
- Central Air
- Walk-in Closet
- Fireplace
- High Ceilings

#### Amenities (`amenities` table)
Property amenities like "Swimming Pool", "Fitness Center", etc.

**Common Amenities:**
- Swimming Pool
- Fitness Center
- 24/7 Security
- Parking Garage
- Pet Friendly
- Elevator
- Garden
- Rooftop Deck

### Junction Tables

#### Property Features (`property_features` table)
Links properties with their features.

**Fields:**
- `property_id` (Foreign Key → properties)
- `feature_id` (Foreign Key → features)
- Timestamps (`created_at`, `updated_at`)

#### Property Amenities (`property_amenities` table)
Links properties with their amenities.

**Fields:**
- `property_id` (Foreign Key → properties)
- `amenity_id` (Foreign Key → amenities)
- `details` (nullable)
- Timestamps (`created_at`, `updated_at`)

## Relationships

### Property Model Relationships
- `residential()` - One-to-One with ResidentialProperty
- `commercial()` - One-to-One with CommercialProperty
- `land()` - One-to-One with LandProperty
- `amenities()` - Many-to-Many with Amenity
- `features()` - Many-to-Many with Feature
- `media()` - One-to-Many with PropertyMedia
- `agent()` - Belongs-To with User

### PropertyMedia Model Features
- Automatic UUID generation for `media_key`
- Automatic URL handling for media files
- Active/Inactive state management
- Resource type filtering
- Media type categorization

## Data Types and Validation

### Numeric Fields
- Prices: decimal(12,2)
- Coordinates: decimal(10,8) for latitude, decimal(11,8) for longitude
- Areas: decimal(10,2)
- Bathrooms: decimal(3,1) in properties table, integer in residential_properties
- All room counts: integer

### Boolean Fields
- `basement`
- `garage`
- `is_active` (for media)

### Special Fields
- UUIDs for media tracking
- Enum types for residential properties
- Soft deletes on main tables
- Automatic timestamps

## Notes
1. The database uses soft deletes for main entities (properties, media)
2. All main tables include standard Laravel timestamps
3. Foreign keys are properly constrained with cascade deletes where appropriate
4. The structure supports multiple property types (residential, commercial, land)
5. Media handling is flexible and supports multiple types and categories
6. Property features and amenities are managed through pivot tables for flexibility
