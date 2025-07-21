# Media Integration Guide

## Overview
This document explains how media (images) are handled in the backend, specifically focusing on the integration with TREB (Toronto Real Estate Board) media services.

## TREB Media Integration

### Media Structure
Each property in TREB can have multiple media files (mostly images) associated with it. Each media file has:
- `MediaKey`: Unique identifier (e.g., "V47338_1")
- `ResourceRecordKey`: The MLS number of the property
- `ImageSizeDescription`: Size variant ("Large", "Medium", etc.)
- `MediaCategory`: Type of media ("Photo", "VirtualTour", etc.)
- `MediaURL`: Direct URL to the media file
- `ModificationTimestamp`: When the media was last modified

### Authentication
All requests to TREB's API require:
- Bearer token authentication
- OData-Version: 4.0 header
- Proper content type headers

## API Endpoints

### 1. Get Property Media
Retrieves all media files for a specific property.

```
GET /api/media/property?mlsNumber={mlsNumber}
```

**Parameters:**
- `mlsNumber`: The MLS number of the property (required)

**Response Example:**
```json
{
  "value": [
    {
      "MediaKey": "V47338_1",
      "ImageSizeDescription": "Large",
      "MediaCategory": "Photo",
      "MediaURL": "https://query.ampre.ca/...",
      "ModificationTimestamp": "2024-01-15T12:00:00Z",
      "ProxyURL": "http://your-domain/api/media/proxy?url=..."
    }
    // ... more media items
  ]
}
```

### 2. Media Proxy
Proxies media files from TREB with proper authentication.

```
GET /api/media/proxy?url={encodedUrl}
```

**Parameters:**
- `url`: Encoded URL of the TREB media endpoint (required)
  Format: `https://query.ampre.ca/odata/Media/{MediaKey}`

**Response:**
- Content-Type: image/* (depends on media type)
- Binary image data

### 3. TREB Media by MLS Number
Alternative endpoint to get media specifically for TREB properties.

```
GET /api/trebmedia/{mlsNumber}
```

**Parameters:**
- `mlsNumber`: The MLS number of the property
- `size`: (optional) Image size variant (defaults to "Large")

## Implementation Details

### Media Flow
1. Frontend requests property media using MLS number
2. Backend queries TREB's OData endpoint with filters:
   ```
   ResourceName eq 'Property' and 
   ResourceRecordKey eq '{mlsNumber}' and 
   ImageSizeDescription eq 'Large'
   ```
3. Backend processes response and adds proxy URLs
4. Frontend uses proxy URLs to display images
5. Proxy endpoint handles authentication with TREB

### Image Proxy Process
1. Extract MediaKey from incoming URL
2. Query TREB's OData endpoint for media metadata
3. Get actual media URL from metadata
4. Fetch media with authentication
5. Stream media back to client

## Usage in Frontend

### Example: Displaying Property Images
```javascript
// Fetch all images for a property
fetch('/api/media/property?mlsNumber=C5555555')
  .then(response => response.json())
  .then(data => {
    data.value.forEach(media => {
      const img = document.createElement('img');
      img.src = media.ProxyURL;
      img.alt = `Property Image ${media.MediaKey}`;
      // Add image to your container
    });
  });
```

### Example: Direct Image Access
```html
<img src="/api/media/proxy?url=https://query.ampre.ca/odata/Media/V47338_1" alt="Property Image">
```

## Error Handling
The API includes comprehensive error handling:
- Invalid MLS numbers
- Missing media files
- Authentication failures
- Network issues
- Invalid media formats

All errors return appropriate HTTP status codes and detailed error messages in JSON format.

## Security Considerations
1. All TREB API tokens are server-side only
2. Media proxy prevents direct access to TREB's API
3. SSL verification is currently disabled for development
4. Rate limiting should be considered for production

## Caching
The proxy endpoint includes Cache-Control headers:
```
Cache-Control: public, max-age=31536000
```
Consider implementing additional caching strategies for production.

## Future Improvements
1. Implement media caching
2. Add support for different image sizes
3. Implement rate limiting
4. Enable SSL verification for production
5. Add support for other media types (virtual tours, documents) 
