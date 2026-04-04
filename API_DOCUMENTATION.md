# Carmotii Car Sharing API Documentation

## Overview

This is a comprehensive REST API for a peer-to-peer car sharing platform similar to Turo. The API supports car listings, bookings, payments (M-Pesa integration), reviews, and user management with role-based permissions.

## Base URL

```
http://localhost:8000/api
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the token in the Authorization header:

```
Authorization: Bearer {token}
```

## Roles

- **user**: Regular users can browse cars and make bookings
- **host**: Users who can list cars (after purchasing a plan)
- **admin**: Administrative access to manage the platform

## API Endpoints

### Authentication

#### Register User
```http
POST /auth/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone_number": "254700000000",
  "date_of_birth": "1990-01-01"
}
```

#### Login
```http
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Get Current User
```http
GET /auth/me
Authorization: Bearer {token}
```

#### Update Profile
```http
PUT /auth/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Smith",
  "phone_number": "254700000001",
  "bio": "Car enthusiast"
}
```

#### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

### Cars

#### Browse Cars (Public)
```http
GET /cars?location=Nairobi&car_type=sedan&min_price=1000&max_price=20000&sort_by=price_low
```

#### Get Car Details (Public)
```http
GET /cars/{id}
```

#### Create Car Listing (Host only)
```http
POST /cars
Authorization: Bearer {token}
Content-Type: application/json

{
  "make": "Toyota",
  "model": "Camry",
  "year": 2020,
  "color": "Silver",
  "license_plate": "KAB 123A",
  "vin": "12345678901234567",
  "type": "sedan",
  "transmission": "automatic",
  "fuel_type": "petrol",
  "seats": 5,
  "doors": 4,
  "description": "Well maintained sedan",
  "daily_price": 3000.00,
  "location_address": "Nairobi CBD",
  "location_city": "Nairobi",
  "latitude": -1.2921,
  "longitude": 36.8219,
  "features": ["Bluetooth", "USB", "Air Conditioning"]
}
```

#### Update Car (Host only)
```http
PUT /cars/{id}
Authorization: Bearer {token}
```

#### Delete Car (Host only)
```http
DELETE /cars/{id}
Authorization: Bearer {token}
```

#### Upload Car Images (Host only)
```http
POST /cars/{id}/images
Authorization: Bearer {token}
Content-Type: multipart/form-data

images[]: [file]
images[]: [file]
```

### Bookings

#### Create Booking
```http
POST /bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "car_id": 1,
  "start_date": "2024-12-01T10:00:00Z",
  "end_date": "2024-12-03T10:00:00Z",
  "pickup_location": {
    "address": "Nairobi CBD"
  },
  "special_requests": "Please provide child seat"
}
```

#### Get Booking Details
```http
GET /bookings/{id}
Authorization: Bearer {token}
```

#### Cancel Booking
```http
PUT /bookings/{id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Change of plans"
}
```

#### Confirm Booking (Host only)
```http
PUT /bookings/{id}/confirm
Authorization: Bearer {token}
```

#### Complete Booking (Host only)
```http
PUT /bookings/{id}/complete
Authorization: Bearer {token}
```

### Reviews

#### Create Review
```http
POST /reviews
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": 1,
  "rating": 5,
  "comment": "Great experience! Car was clean and well maintained."
}
```

#### Get Car Reviews (Public)
```http
GET /cars/{car_id}/reviews
```

#### Update Review
```http
PUT /reviews/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "rating": 4,
  "comment": "Updated review text"
}
```

#### Delete Review
```http
DELETE /reviews/{id}
Authorization: Bearer {token}
```

### Payments

#### Initiate M-Pesa Payment
```http
POST /payments/mpesa/initiate
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "booking_payment",
  "phone_number": "254700000000",
  "booking_id": 1
}
```

For listing fee:
```json
{
  "type": "listing_fee",
  "phone_number": "254700000000",
  "pricing_plan_id": 1
}
```

#### M-Pesa Callback (Webhook)
```http
POST /payments/mpesa/callback
Content-Type: application/json

{
  "transaction_id": "CAR123456789",
  "status": "completed",
  "mpesa_receipt": "ABC123XYZ"
}
```

#### Get Payment Details
```http
GET /payments/{id}
Authorization: Bearer {token}
```

### Pricing Plans

#### Get Pricing Plans (Public)
```http
GET /pricing-plans
```

#### Subscribe to Plan
```http
POST /subscribe
Authorization: Bearer {token}
Content-Type: application/json

{
  "pricing_plan_id": 1
}
```

#### Get Current Subscription
```http
GET /subscription
Authorization: Bearer {token}
```

### User Management

#### Get User Cars
```http
GET /user/cars
Authorization: Bearer {token}
```

#### Get User Bookings
```http
GET /user/bookings
Authorization: Bearer {token}
```

#### Get User Reviews
```http
GET /user/reviews
Authorization: Bearer {token}
```

### Admin Endpoints

#### Get All Users
```http
GET /admin/users
Authorization: Bearer {admin_token}
```

#### Verify User
```http
PUT /admin/users/{id}/verify
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "verification_status": "verified"
}
```

#### Ban User
```http
PUT /admin/users/{id}/ban
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "is_banned": true,
  "ban_reason": "Violation of terms"
}
```

#### Get Pending Cars
```http
GET /admin/cars/pending
Authorization: Bearer {admin_token}
```

#### Approve/Reject Car
```http
PUT /admin/cars/{id}/approve
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "approved": true
}
```

## Error Responses

All error responses follow this format:

```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

Common HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## Rate Limiting

API requests are limited to 60 requests per minute per IP address.

## Testing

Use the following test credentials (after running database seeders):

- **Admin**: admin@carmotii.com / password
- **Host**: host@carmotii.com / password  
- **User**: user@carmotii.com / password

## Features Implemented

✅ User authentication and role-based permissions
✅ Car CRUD operations with image uploads
✅ Advanced car search and filtering
✅ Booking system with availability checking
✅ Review and rating system with moderation
✅ M-Pesa payment integration (simulated)
✅ Subscription-based pricing plans
✅ Admin dashboard functionality
✅ Comprehensive validation and error handling
✅ API documentation

## Security Features

- Laravel Sanctum for API authentication
- Role-based access control
- Input validation and sanitization
- SQL injection protection
- CORS configuration
- Rate limiting
