# Backend Logout Implementation

This document explains how the logout functionality is implemented in the Laravel backend to work seamlessly with the Vue.js frontend.

## Overview

The logout functionality is implemented across multiple layers:
1. **AuthController** - API endpoint handling
2. **AuthService** - Business logic layer
3. **ApiAuthentication Middleware** - Authentication validation
4. **User Model** - Sanctum token management

## API Endpoint

### Logout Endpoint
```
POST /api/auth/logout
```

### Headers Required
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "device_name": "web"
}
```

### Success Response (200)
```json
{
  "message": "Logged out successfully"
}
```

### Error Responses

#### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "device_name": ["The device name field is required."]
  }
}
```

#### Unauthorized (401)
```json
{
  "message": "Unauthorized",
  "status": 401
}
```

#### Server Error (500)
```json
{
  "message": "Server error",
  "status": 500
}
```

## Implementation Details

### 1. AuthController::logout()

**Location:** `app/Http/Controllers/API/AuthController.php`

**Responsibilities:**
- Validates the request data
- Authenticates the user
- Delegates logout logic to AuthService
- Returns appropriate HTTP responses

**Key Features:**
- Requires `device_name` parameter
- Validates user authentication
- Handles errors gracefully
- Logs logout activities

### 2. AuthService::logout()

**Location:** `app/Services/AuthService.php`

**Responsibilities:**
- Manages token deletion logic
- Handles device-specific logout
- Logs successful logout events
- Throws exceptions for error handling

**Key Features:**
- Deletes tokens for specific device only
- Maintains tokens for other devices
- Comprehensive error logging
- Clean separation of concerns

### 3. ApiAuthentication Middleware

**Location:** `app/Http/Middleware/ApiAuthentication.php`

**Responsibilities:**
- Validates authentication for protected routes
- Provides consistent error responses
- Logs unauthorized access attempts

**Key Features:**
- Consistent 401 responses
- Detailed logging for security
- Handles authentication errors gracefully

## Route Configuration

**Location:** `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
```

## Security Features

### 1. Device-Specific Token Management
- Only deletes tokens for the specified device
- Preserves tokens for other devices (mobile, tablet, etc.)
- Allows multi-device authentication

### 2. Validation
- Requires `device_name` parameter
- Validates string length and format
- Prevents malicious requests

### 3. Error Handling
- Graceful handling of invalid tokens
- Proper HTTP status codes
- Detailed error logging for debugging

### 4. Logging
- Logs successful logout events
- Tracks unauthorized access attempts
- Records error details for troubleshooting

## Frontend Integration

### Expected Frontend Behavior

1. **Send Request:**
   ```javascript
   await axios.post('/api/auth/logout', {
     device_name: sessionStorage.getItem("device_name")
   });
   ```

2. **Handle Response:**
   ```javascript
   // Success: Clear local storage and redirect
   sessionStorage.removeItem('auth_token');
   sessionStorage.removeItem('user_data');
   router.push('/landing');
   
   // Error: Handle appropriately
   console.error('Logout failed:', error);
   ```

3. **Error Handling:**
   ```javascript
   // 401: Redirect to login
   // 422: Show validation errors
   // 500: Show server error message
   ```

## Testing

### Manual Testing
```bash
# Test successful logout
curl -X POST http://your-api.com/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_name": "web"}'

# Test validation error
curl -X POST http://your-api.com/api/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'

# Test unauthorized
curl -X POST http://your-api.com/api/auth/logout \
  -H "Content-Type: application/json" \
  -d '{"device_name": "web"}'
```

### Automated Testing
Run the comprehensive test suite:
```bash
php artisan test tests/Feature/AuthLogoutTest.php
```

## Database Schema

### Personal Access Tokens Table
```sql
CREATE TABLE personal_access_tokens (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    tokenable_type varchar(255) NOT NULL,
    tokenable_id bigint unsigned NOT NULL,
    name varchar(255) NOT NULL,
    token varchar(64) NOT NULL UNIQUE,
    abilities text,
    last_used_at timestamp NULL,
    expires_at timestamp NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    PRIMARY KEY (id),
    UNIQUE KEY personal_access_tokens_token_unique (token),
    KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
);
```

## Configuration

### CORS Configuration
**Location:** `config/cors.php`
```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'api/auth/init'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];
```

### Sanctum Configuration
**Location:** `config/sanctum.php`
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 
    'localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,127.0.0.1:5173,::1'
)),
```

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Ensure frontend domain is in `SANCTUM_STATEFUL_DOMAINS`
   - Check CORS configuration in `config/cors.php`

2. **401 Unauthorized**
   - Verify token is valid and not expired
   - Check if token exists in database
   - Ensure proper Authorization header format

3. **422 Validation Errors**
   - Ensure `device_name` is provided
   - Check string length (max 255 characters)
   - Verify Content-Type header

4. **500 Server Errors**
   - Check Laravel logs in `storage/logs/laravel.log`
   - Verify database connection
   - Ensure proper Sanctum configuration

### Debug Steps

1. **Check Token Validity:**
   ```bash
   php artisan tinker
   >>> $token = PersonalAccessToken::where('token', hash('sha256', 'YOUR_TOKEN'))->first();
   >>> dd($token);
   ```

2. **Verify User Authentication:**
   ```bash
   php artisan tinker
   >>> $user = User::find(1);
   >>> dd($user->tokens);
   ```

3. **Check Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Performance Considerations

1. **Token Cleanup**
   - Consider implementing token expiration
   - Clean up expired tokens periodically
   - Monitor token table size

2. **Database Optimization**
   - Index on `tokenable_id` and `name` columns
   - Consider partitioning for large token tables
   - Regular cleanup of old tokens

3. **Caching**
   - Cache user data for frequently accessed information
   - Use Redis for session storage if needed
   - Implement token caching for high-traffic applications

## Security Best Practices

1. **Token Management**
   - Use short-lived tokens when possible
   - Implement token rotation
   - Monitor for suspicious token usage

2. **Logging**
   - Log all authentication events
   - Monitor failed logout attempts
   - Track device usage patterns

3. **Validation**
   - Always validate input data
   - Sanitize device names
   - Implement rate limiting

4. **Error Handling**
   - Don't expose sensitive information in errors
   - Use consistent error response format
   - Log errors for debugging without exposing details 
