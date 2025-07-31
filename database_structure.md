# Database Structure Documentation

## Overview
This document outlines the database structure for the real estate management system. The system is designed to handle different types of properties (Residential, Commercial, and Land) along with their associated features, amenities, and media.

## Core Tables

### Properties (Base Table)
The main properties table that serves as the parent for all property types.

```sql
properties
├── id (Primary Key)
├── agent_id (Foreign Key -> users)
├── name
├── description
├── price
├── address
├── city
├── state
├── zip_code
├── type
├── status (default: 'for_sale')
├── bedrooms (nullable)
├── bathrooms (nullable)
├── size (in square feet)
├── year_built (nullable)
├── virtual_tour_url (nullable)
├── neighborhood (nullable)
├── latitude (nullable)
└── longitude (nullable)
```

## Property Types (Inheritance Tables)

### 1. Residential Properties
Specific details for residential properties.

```sql
residential_properties
├── id (Primary Key)
├── property_id (Foreign Key -> properties)
├── residential_type (enum: single_family, apartment, townhouse, duplex, condo, villa)
├── bedrooms
├── bathrooms
├── total_rooms
├── floor_area (in square feet)
├── stories
├── basement (boolean)
├── garage (boolean)
└── garage_size (nullable)
```

### 2. Commercial Properties
Specific details for commercial properties.

```sql
commercial_properties
├── id (Primary Key)
├── property_id (Foreign Key -> properties)
├── commercial_type (enum: office, retail, industrial, warehouse, restaurant, mixed_use)
├── total_area (in square feet)
├── floors
├── units
├── loading_docks
├── ceiling_height (in feet)
├── zoning
├── current_use
└── potential_use
```

### 3. Land Properties
Specific details for land properties.

```sql
land_properties
├── id (Primary Key)
├── property_id (Foreign Key -> properties)
├── land_type (enum: residential_lot, commercial_lot, agricultural, recreational, industrial_lot)
├── topography
├── soil_type
├── utilities_available (boolean)
├── road_frontage (in feet)
├── zoning
├── current_use
└── potential_use
```

## Supporting Tables

### Property Media
Handles all media assets related to properties.

```sql
property_media
├── id (Primary Key)
├── media_key (UUID)
├── property_id (Foreign Key -> properties)
├── resource_name (default: 'Property')
├── resource_record_key
├── media_type (image, video, virtual-tour, document)
├── media_category (nullable)
├── image_size_description (nullable)
├── media_url
├── media_caption (nullable)
├── media_description (nullable)
├── is_active (boolean)
├── order_index
└── modification_timestamp
```

### Amenities and Property Amenities
Manages property amenities with a many-to-many relationship.

```sql
amenities
├── id (Primary Key)
├── name
├── icon (nullable)
├── description (nullable)
└── category (nullable)

property_amenities
├── id (Primary Key)
├── property_id (Foreign Key -> properties)
├── amenity_id (Foreign Key -> amenities)
└── details (nullable)
```

## Relationships

1. **One-to-One Relationships**:
   - A property can have only one specific type (residential, commercial, or land)
   - Each specific property type belongs to one base property

2. **One-to-Many Relationships**:
   - One agent (user) can have many properties
   - One property can have many media items

3. **Many-to-Many Relationships**:
   - Properties and Amenities (through property_amenities table)

## Additional Features

1. **Soft Deletes**:
   - All tables implement soft deletes for data recovery
   - Deleted records are marked rather than removed

2. **Timestamps**:
   - All tables track created_at and updated_at
   - Media tracks modification_timestamp separately

3. **Indexing**:
   - Foreign keys are indexed
   - Media table has additional indexes on resource_name, resource_record_key, media_type, and modification_timestamp

## Data Integrity

1. **Cascading Deletes**:
   - When a property is deleted, all related records (media, amenities, type-specific details) are deleted
   - When an amenity is deleted, all property_amenities entries are removed

2. **Unique Constraints**:
   - Property amenities have a unique constraint on [property_id, amenity_id]
   - Media keys are unique
