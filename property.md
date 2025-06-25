# Property Management Database Schema

## Overview
This schema covers all property-related functionality in the RCR application, including:
- Property listings (Residential, Commercial, Land)
- Property features and amenities
- Media management
- Property interactions
- Access control

## PostgreSQL Schema

```sql
-- Enums for various property-related types
CREATE TYPE property_type AS ENUM (
    'residential',
    'commercial',
    'land'
);

CREATE TYPE property_status AS ENUM (
    'draft',
    'pending_review',
    'active',
    'under_contract',
    'sold',
    'off_market',
    'archived'
);

CREATE TYPE residential_type AS ENUM (
    'single_family',
    'apartment',
    'townhouse',
    'duplex',
    'condo',
    'villa'
);

CREATE TYPE commercial_type AS ENUM (
    'office',
    'retail',
    'industrial',
    'warehouse',
    'restaurant',
    'mixed_use'
);

CREATE TYPE land_type AS ENUM (
    'residential_lot',
    'commercial_lot',
    'agricultural',
    'recreational',
    'industrial_lot'
);

-- Base properties table
CREATE TABLE properties (
    id SERIAL PRIMARY KEY,
    agent_id INTEGER NOT NULL REFERENCES users(id),
    property_type property_type NOT NULL,
    status property_status NOT NULL DEFAULT 'draft',
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'United States',
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    lot_size DECIMAL(10,2), -- in square feet
    year_built INTEGER,
    parking_spots INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP WITH TIME ZONE,
    archived_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT valid_price CHECK (price >= 0),
    CONSTRAINT valid_lot_size CHECK (lot_size >= 0),
    CONSTRAINT valid_year_built CHECK (year_built >= 1800 AND year_built <= EXTRACT(YEAR FROM CURRENT_DATE)),
    media JSONB DEFAULT '[]'::jsonb
);

-- Residential properties specific details
CREATE TABLE residential_properties (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    residential_type residential_type NOT NULL,
    bedrooms INTEGER NOT NULL,
    bathrooms DECIMAL(3,1) NOT NULL, -- Allows for half baths
    total_rooms INTEGER NOT NULL,
    floor_area DECIMAL(10,2) NOT NULL, -- in square feet
    stories INTEGER DEFAULT 1,
    basement BOOLEAN DEFAULT false,
    garage BOOLEAN DEFAULT false,
    garage_size INTEGER,
    year_renovated INTEGER,
    CONSTRAINT valid_bedrooms CHECK (bedrooms >= 0),
    CONSTRAINT valid_bathrooms CHECK (bathrooms >= 0),
    CONSTRAINT valid_floor_area CHECK (floor_area >= 0),
    CONSTRAINT valid_stories CHECK (stories >= 1)
);

-- Commercial properties specific details
CREATE TABLE commercial_properties (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    commercial_type commercial_type NOT NULL,
    total_area DECIMAL(10,2) NOT NULL, -- in square feet
    floors INTEGER NOT NULL DEFAULT 1,
    units INTEGER,
    loading_docks INTEGER DEFAULT 0,
    ceiling_height DECIMAL(5,2), -- in feet
    zoning VARCHAR(100),
    current_use VARCHAR(255),
    potential_use TEXT,
    CONSTRAINT valid_total_area CHECK (total_area >= 0),
    CONSTRAINT valid_floors CHECK (floors >= 1)
);

-- Land properties specific details
CREATE TABLE land_properties (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    land_type land_type NOT NULL,
    topography TEXT,
    soil_type VARCHAR(100),
    utilities_available BOOLEAN DEFAULT false,
    road_frontage DECIMAL(10,2), -- in feet
    zoning VARCHAR(100),
    current_use VARCHAR(255),
    potential_use TEXT,
    CONSTRAINT valid_road_frontage CHECK (road_frontage >= 0)
);

-- Property amenities
CREATE TABLE amenities (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Property-amenity relationship
CREATE TABLE property_amenities (
    property_id INTEGER REFERENCES properties(id) ON DELETE CASCADE,
    amenity_id INTEGER REFERENCES amenities(id) ON DELETE CASCADE,
    details VARCHAR(255),
    PRIMARY KEY (property_id, amenity_id)
);

# Property Media Management with AWS S3

## Updated Media Schema

```sql
-- Media types enum for better type safety
CREATE TYPE media_type AS ENUM (
    'image',
    'video',
    'virtual_tour',
    'document',
    '3d_model'
);

-- Media status for processing states
CREATE TYPE media_status AS ENUM (
    'pending',
    'processing',
    'completed',
    'failed'
);

-- Property media table with AWS S3 specific fields
CREATE TABLE property_media (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    media_type media_type NOT NULL,
    
    -- AWS S3 specific fields
    bucket_name VARCHAR(63) NOT NULL,  -- AWS S3 bucket name
    s3_key VARCHAR(1024) NOT NULL,     -- Full S3 object key
    s3_version_id VARCHAR(1024),       -- S3 version ID (if versioning enabled)
    
    -- Content metadata
    original_filename VARCHAR(255) NOT NULL,
    content_type VARCHAR(100) NOT NULL, -- MIME type
    file_size BIGINT NOT NULL,         -- in bytes
    md5_hash VARCHAR(32),              -- for integrity checking
    
    -- Image/Video specific metadata
    width INTEGER,                     -- for images/videos
    height INTEGER,                    -- for images/videos
    duration INTEGER,                  -- for videos (in seconds)
    
    -- Processing and status
    status media_status NOT NULL DEFAULT 'pending',
    processing_metadata JSONB,         -- Store AWS processing metadata
    
    -- Access control
    is_public BOOLEAN DEFAULT false,
    acl VARCHAR(20) DEFAULT 'private', -- S3 ACL (private, public-read, etc.)
    presigned_url_expiry TIMESTAMP WITH TIME ZONE, -- For temporary URLs
    
    -- Organization
    title VARCHAR(255),
    description TEXT,
    is_featured BOOLEAN DEFAULT false,
    order_index INTEGER NOT NULL DEFAULT 0,
    tags TEXT[],                       -- Array of tags for searching
    
    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT valid_file_size CHECK (file_size > 0),
    CONSTRAINT valid_dimensions CHECK (
        (media_type = 'image' AND width IS NOT NULL AND height IS NOT NULL) OR
        (media_type != 'image')
    )
);

-- Indexes for common queries
CREATE INDEX idx_property_media_property_id ON property_media(property_id);
CREATE INDEX idx_property_media_type ON property_media(media_type);
CREATE INDEX idx_property_media_status ON property_media(status);
CREATE INDEX idx_property_media_tags ON property_media USING GIN(tags);

-- For efficient JSON queries if needed
CREATE INDEX idx_property_media_metadata ON property_media USING GIN(processing_metadata);

-- Function to generate presigned URL (pseudo-code, implement in application layer)
CREATE OR REPLACE FUNCTION generate_presigned_url(
    p_media_id INTEGER,
    p_expiry_minutes INTEGER DEFAULT 60
) RETURNS TEXT AS $$
BEGIN
    -- Implementation would be in application layer
    -- This is just a placeholder for documentation
    RETURN 'https://example.com/presigned-url';
END;
$$ LANGUAGE plpgsql;
```

## AWS S3 Implementation Recommendations

1. **Bucket Organization**:
   ```
   my-real-estate-app/
   ├── properties/
   │   ├── {property_id}/
   │   │   ├── images/
   │   │   │   ├── original/
   │   │   │   ├── thumbnails/
   │   │   │   └── compressed/
   │   │   ├── videos/
   │   │   │   ├── original/
   │   │   │   ├── transcoded/
   │   │   │   └── thumbnails/
   │   │   └── documents/
   │   └── ...
   └── temp/
       └── uploads/
   ```

2. **File Naming Convention**:
   ```
   {property_id}/{media_type}/{uuid}-{original_filename}
   ```

3. **AWS Services Integration**:
   - Use S3 for storage
   - CloudFront for CDN delivery
   - Lambda for image/video processing
   - MediaConvert for video transcoding
   - Rekognition for image analysis

4. **Media Processing Workflow**:
   ```mermaid
   graph TD
       A[Upload File] --> B[Store in S3]
       B --> C{Media Type?}
       C -->|Image| D[Image Processing]
       C -->|Video| E[Video Processing]
       C -->|Document| F[Document Processing]
       D --> G[Generate Variants]
       E --> H[Transcode]
       F --> I[Generate Preview]
       G --> J[Update DB]
       H --> J
       I --> J
   ```

5. **Security Best Practices**:
   - Use server-side encryption (SSE-S3 or SSE-KMS)
   - Enable versioning for important buckets
   - Use presigned URLs for temporary access
   - Implement proper bucket policies and CORS
   - Regular security audits

6. **Performance Optimization**:
   - Use CloudFront for content delivery
   - Implement caching strategies
   - Use appropriate compression
   - Generate and store multiple resolutions
   - Implement lazy loading in frontend

7. **Example Usage**:
```sql
-- Insert new media
INSERT INTO property_media (
    property_id,
    media_type,
    bucket_name,
    s3_key,
    original_filename,
    content_type,
    file_size,
    width,
    height
) VALUES (
    123,
    'image',
    'my-real-estate-app',
    'properties/123/images/original/abc123-house-front.jpg',
    'house-front.jpg',
    'image/jpeg',
    1048576,
    1920,
    1080
);

-- Query media for a property
SELECT 
    id,
    media_type,
    bucket_name || '/' || s3_key as s3_path,
    CASE 
        WHEN presigned_url_expiry > CURRENT_TIMESTAMP 
        THEN generate_presigned_url(id)
        ELSE NULL 
    END as access_url
FROM property_media
WHERE property_id = 123
ORDER BY order_index;
```

8. **Backup and Recovery**:
   - Enable versioning on S3 buckets
   - Regular backups of media metadata
   - Cross-region replication for disaster recovery
   - Implement soft delete with recovery period

9. **Monitoring and Maintenance**:
   - Track media processing status
   - Monitor storage usage and costs
   - Implement cleanup for temporary files
   - Regular integrity checks
   - Usage analytics and reporting
```

-- Property views tracking
CREATE TABLE property_views (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    ip_address INET,
    user_agent VARCHAR(255),
    view_duration INTEGER, -- in seconds
    viewed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Saved/favorited properties
CREATE TABLE saved_properties (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (property_id, user_id)
);

-- Property inquiries
CREATE TABLE property_inquiries (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'responded', 'closed')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Property price history
CREATE TABLE property_price_history (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    price DECIMAL(12,2) NOT NULL,
    change_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    change_type VARCHAR(20) NOT NULL CHECK (change_type IN ('initial', 'increase', 'decrease')),
    notes TEXT
);

-- Property open houses
CREATE TABLE open_houses (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT valid_time_range CHECK (end_time > start_time)
);

-- Property reviews (for sold properties)
CREATE TABLE property_reviews (
    id SERIAL PRIMARY KEY,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for better query performance
CREATE INDEX idx_properties_agent_id ON properties(agent_id);
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_type ON properties(property_type);
CREATE INDEX idx_properties_price ON properties(price);
CREATE INDEX idx_properties_location ON properties(city, state, postal_code);
CREATE INDEX idx_property_views_property_id ON property_views(property_id);
CREATE INDEX idx_saved_properties_user_id ON saved_properties(user_id);
CREATE INDEX idx_property_media_property_id ON property_media(property_id);
CREATE INDEX idx_property_amenities_property_id ON property_amenities(property_id);

-- Full-text search index
CREATE INDEX idx_properties_search ON properties USING GIN (
    to_tsvector('english',
        coalesce(title, '') || ' ' ||
        coalesce(description, '') || ' ' ||
        coalesce(address_line1, '') || ' ' ||
        coalesce(city, '') || ' ' ||
        coalesce(state, '')
    )
);

-- Triggers for updated_at timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_properties_modtime
    BEFORE UPDATE ON properties
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Function to check if a user can access a property
CREATE OR REPLACE FUNCTION can_access_property(p_user_id INTEGER, p_property_id INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    v_user_role VARCHAR(50);
    v_agent_id INTEGER;
BEGIN
    -- Get user's role
    SELECT role INTO v_user_role
    FROM users
    WHERE id = p_user_id;

    -- Get property's agent_id
    SELECT agent_id INTO v_agent_id
    FROM properties
    WHERE id = p_property_id;

    -- Admin can access all properties
    IF v_user_role = 'admin' THEN
        RETURN TRUE;
    END IF;

    -- Agent can access their own properties
    IF v_user_role = 'agent' AND v_agent_id = p_user_id THEN
        RETURN TRUE;
    END IF;

    -- Clients can access active properties
    IF v_user_role = 'client' THEN
        RETURN EXISTS (
            SELECT 1
            FROM properties
            WHERE id = p_property_id
            AND status = 'active'
        );
    END IF;

    RETURN FALSE;
END;
$$ LANGUAGE plpgsql;

-- Views for common queries
CREATE VIEW active_properties AS
SELECT p.*, 
       CASE 
           WHEN r.property_id IS NOT NULL THEN 'residential'
           WHEN c.property_id IS NOT NULL THEN 'commercial'
           WHEN l.property_id IS NOT NULL THEN 'land'
       END as detailed_type
FROM properties p
LEFT JOIN residential_properties r ON p.id = r.property_id
LEFT JOIN commercial_properties c ON p.id = c.property_id
LEFT JOIN land_properties l ON p.id = l.property_id
WHERE p.status = 'active';

CREATE VIEW property_stats AS
SELECT 
    p.id,
    p.title,
    p.property_type,
    p.status,
    COUNT(DISTINCT pv.id) as view_count,
    COUNT(DISTINCT sp.id) as save_count,
    COUNT(DISTINCT pi.id) as inquiry_count
FROM properties p
LEFT JOIN property_views pv ON p.id = pv.property_id
LEFT JOIN saved_properties sp ON p.id = sp.property_id
LEFT JOIN property_inquiries pi ON p.id = pi.property_id
GROUP BY p.id, p.title, p.property_type, p.status;

-- Create an index for JSON querying if needed
CREATE INDEX idx_properties_media ON properties USING GIN (media);

-- Example queries:
-- Get all images for a property
SELECT media->'images' FROM properties WHERE id = 1;

-- Get main image
SELECT media->'images'->0->'url' FROM properties WHERE id = 1;

-- Update or add new media
UPDATE properties 
SET media = jsonb_set(
    media, 
    '{images}', 
    media->'images' || '{"url": "new/image.jpg", "type": "interior", "order": 2}'::jsonb
)
WHERE id = 1;
```

## Access Patterns

### Client Access
```