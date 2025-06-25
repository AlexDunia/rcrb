# Frontend Authentication Guide

## Endpoints Overview

### Public Endpoints

1. **Initialize Authentication**
   - `GET /api/auth/init`
   - Sets up CSRF protection
   - Required before any auth operations

2. **Register**
   - `POST /api/auth/register`
   - Body: `{ name, email, password, role, device_name }`
   - Returns: User data + access token

3. **Login**
   - `POST /api/auth/login`
   - Body: `{ email, password, device_name }`
   - Returns: User data + access token

4. **Google OAuth**
   - Get URL: `GET /auth/google/url`
   - Callback: `/auth/google/callback`
   - Returns to: `{frontend_url}/social-auth-callback`

### Protected Endpoints
Require 'Authorization: Bearer {token}' header

1. **Verify Token**
   - `GET /api/auth/verify`
   - Checks token validity

2. **Get User**
   - `GET /api/auth/user`
   - Returns current user data

3. **Logout**
   - `POST /api/auth/logout`
   - Invalidates current token

## Implementation Steps

1. **Setup**
   ```javascript
   // First call on app load
   await fetch('/api/auth/init', {
     credentials: 'include'
   });
   ```

2. **Login Flow**
   ```javascript
   const response = await fetch('/api/auth/login', {
     method: 'POST',
     headers: {
       'Content-Type': 'application/json'
     },
     body: JSON.stringify({
       email: userEmail,
       password: userPassword,
       device_name: 'web'
     })
   });

   const { token, user } = await response.json();
   // Store token securely
   // Store user data in state
   ```

3. **Protected Requests**
   ```javascript
   const headers = {
     'Authorization': `Bearer ${token}`,
     'Content-Type': 'application/json'
   };
   ```

## Error Handling

- 401: Token expired/invalid → Redirect to login
- 422: Validation error → Show field errors
- 500: Server error → Show generic error

## Security Notes

1. Store token securely (sessionStorage preferred)
2. Always include Authorization header for protected routes
3. Clear token on logout/errors
4. Verify token before accessing protected routes

## User Roles
- 'client': Regular user
- 'agent': Property agent
- Set during registration
- Cannot be changed later

For detailed API responses and error formats, consult the API documentation. 
