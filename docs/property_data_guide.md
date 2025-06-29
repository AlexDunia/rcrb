# Property Data Integration Guide

## Data Structure Overview

### Base Property Information
All properties (residential, commercial, land) share these base fields:
```json
{
  "id": "integer",
  "agent_id": "integer",
  "property_type": "enum('residential', 'commercial', 'land')",
  "status": "enum('active', 'pending', 'sold', 'rented', 'inactive')",
  "title": "string",
  "slug": "string",
  "description": "text",
  "price": "decimal",
  "address_line1": "string",
  "address_line2": "string|null",
  "city": "string",
  "state": "string",
  "postal_code": "string",
  "country": "string",
  "latitude": "decimal|null",
  "longitude": "decimal|null",
  "lot_size": "decimal",
  "year_built": "integer|null",
  "parking_spots": "integer"
}
```

### Type-Specific Information

#### Residential Properties
```json
{
  "residential_type": "enum('single_family', 'apartment', 'townhouse', 'duplex', 'condo', 'villa')",
  "bedrooms": "integer",
  "bathrooms": "integer",
  "total_rooms": "integer",
  "floor_area": "decimal",
  "stories": "integer",
  "basement": "boolean",
  "garage": "boolean",
  "garage_size": "integer|null"
}
```

#### Commercial Properties
```json
{
  "commercial_type": "enum('office', 'retail', 'industrial', 'warehouse', 'restaurant', 'mixed_use')",
  "total_area": "decimal",
  "floors": "integer",
  "units": "integer",
  "loading_docks": "integer",
  "ceiling_height": "decimal",
  "zoning": "string",
  "current_use": "string",
  "potential_use": "string"
}
```

#### Land Properties
```json
{
  "land_type": "enum('residential_lot', 'commercial_lot', 'agricultural', 'recreational', 'industrial_lot')",
  "topography": "string",
  "soil_type": "string",
  "utilities_available": "boolean",
  "road_frontage": "decimal",
  "zoning": "string",
  "current_use": "string",
  "potential_use": "string"
}
```

## Common API Endpoints to Create

1. **List Properties**
```
GET /api/properties
Query Parameters:
- type: residential|commercial|land
- status: active|pending|sold|rented|inactive
- city: string
- min_price: decimal
- max_price: decimal
- page: integer
- per_page: integer
```

2. **Get Single Property**
```
GET /api/properties/{id}
Response includes:
- Base property info
- Type-specific details
- Media
- Amenities
- Agent information
```

3. **Properties by Agent**
```
GET /api/agents/{id}/properties
GET /api/agents/me/properties (for logged-in agent)
```

4. **Client Interactions**
```
POST /api/properties/{id}/inquire
POST /api/properties/{id}/favorite
GET /api/users/me/favorites
```

## Frontend Integration Questions

1. **Authentication & Authorization**
   - How will you handle different user roles (admin, agent, client)?
   - What routes should be protected?
   - How will you store and refresh tokens?

2. **Property Listing Page**
   - How will you implement filtering and sorting?
   - Will you use server-side or client-side pagination?
   - How will you handle image lazy loading?
   - Will you implement a map view?

3. **Property Detail Page**
   - How will you structure the layout for different property types?
   - Will you implement image galleries/carousels?
   - How will you handle property inquiries?
   - Will you add social sharing?

4. **Agent Dashboard**
   - What metrics will you show?
   - How will you handle property CRUD operations?
   - Will you implement real-time notifications?

5. **Search Implementation**
   - Will you use instant search?
   - How will you handle geolocation-based searches?
   - Will you implement saved searches?

## Example API Responses

### List Properties Response
```json
{
  "data": [
    {
      "id": 1,
      "property_type": "residential",
      "title": "Modern Single Family Home",
      "price": 450000,
      "address": {
        "line1": "123 Main St",
        "city": "Austin",
        "state": "TX"
      },
      "thumbnail": "https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/BG_eovnvt.png",
      "specs": {
        "bedrooms": 4,
        "bathrooms": 3,
        "area": 2500
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 100
  }
}
```

### Property Detail Response
```json
{
  "id": 1,
  "property_type": "residential",
  "title": "Modern Single Family Home",
  "description": "Beautiful property with modern amenities",
  "price": 450000,
  "status": "active",
  "address": {
    "line1": "123 Main St",
    "line2": null,
    "city": "Austin",
    "state": "TX",
    "postal_code": "78701",
    "country": "United States",
    "coordinates": {
      "latitude": 30.2672,
      "longitude": -97.7431
    }
  },
  "details": {
    "residential_type": "single_family",
    "bedrooms": 4,
    "bathrooms": 3,
    "total_rooms": 8,
    "floor_area": 2500,
    "stories": 2,
    "basement": true,
    "garage": true,
    "garage_size": 2
  },
  "media": [
    {
      "id": 1,
      "type": "image",
      "url": "https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/BG_eovnvt.png",
      "is_featured": true
    }
  ],
  "amenities": [
    {
      "id": 1,
      "name": "Swimming Pool",
      "category": "residential",
      "details": "Heated pool with spa"
    }
  ],
  "agent": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890"
  }
}
```

## Frontend Implementation Tips

1. **State Management**
   - Consider using Redux/Vuex for property data
   - Implement caching for frequently accessed data
   - Handle loading and error states

2. **Component Structure**
   - Create reusable property card components
   - Implement type-specific detail components
   - Use skeleton loaders for better UX

3. **Form Handling**
   - Implement proper validation for inquiries
   - Use step forms for property creation
   - Handle image uploads efficiently

4. **Performance**
   - Implement infinite scroll or pagination
   - Use image optimization and lazy loading
   - Consider implementing service workers

5. **User Experience**
   - Add loading indicators
   - Implement proper error handling
   - Add success/error notifications
   - Consider adding property comparison feature

## Next Steps

1. Create API endpoints following RESTful conventions
2. Implement authentication middleware
3. Create frontend components for each property type
4. Implement search and filtering functionality
5. Add property management features for agents
6. Implement user favorites and history
7. Add admin dashboard for property oversight
