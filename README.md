# Carmotii API Backend

A comprehensive Laravel REST API backend for a peer-to-peer car sharing platform, similar to Turo. This API serves the Carmotii frontend application with complete car rental management, booking system, payment processing, and user administration features.

## 🚗 Repository Information

**Repository Name**: `carmotii-api`  
**Description**: Laravel REST API for peer-to-peer car sharing platform with M-Pesa integration  
**Version**: 1.0.0  
**Framework**: Laravel 13.x

## 📋 Features

### Core Functionality
- 🔐 **Authentication**: JWT-based authentication with Laravel Sanctum
- 👥 **User Management**: Role-based access control (User, Host, Admin)
- 🚗 **Car Management**: Complete CRUD with image uploads and availability tracking
- 📅 **Booking System**: Advanced booking with conflict prevention and status management
- ⭐ **Review System**: User ratings and reviews with moderation
- 💳 **Payment Integration**: M-Pesa payment processing with callback handling
- 💰 **Pricing Plans**: Subscription-based tier system for car hosts
- 🛡️ **Admin Panel**: Comprehensive admin tools for platform management

### Technical Features
- RESTful API design with proper HTTP methods
- Comprehensive validation and error handling
- Role-based permissions with Spatie Laravel Permission
- Database migrations with proper relationships
- File upload system for car images
- Rate limiting and security measures
- API documentation with examples

## 🏗️ Architecture

```
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   ├── CarController.php
│   │   ├── BookingController.php
│   │   ├── ReviewController.php
│   │   ├── PaymentController.php
│   │   ├── UserController.php
│   │   └── PricingPlanController.php
│   └── Models/
│       ├── User.php
│       ├── Car.php
│       ├── Booking.php
│       ├── Review.php
│       ├── Payment.php
│       ├── PricingPlan.php
│       ├── UserSubscription.php
│       └── CarImage.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── API_DOCUMENTATION.md
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.3+
- Composer
- MySQL/SQLite Database
- Laravel 13.x

### Installation

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd carmotii-api
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

### Test Credentials
After running seeders, use these test accounts:
- **Admin**: admin@carmotii.com / password
- **Host**: host@carmotii.com / password  
- **User**: user@carmotii.com / password

## 📚 API Documentation

Complete API documentation is available in [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected endpoints require Bearer token:
```http
Authorization: Bearer {token}
```

## 🔧 Development

### Available Commands
```bash
# Run tests
php artisan test

# Generate API documentation
php artisan api:docs

# Clear cache
php artisan cache:clear

# Optimize for production
php artisan optimize
```

### Database Schema
The application uses 8 main tables:
- `users` - User accounts and profiles
- `cars` - Car listings with details
- `car_images` - Car photo gallery
- `bookings` - Rental bookings and schedules
- `reviews` - User ratings and feedback
- `payments` - Transaction records (M-Pesa)
- `pricing_plans` - Subscription tiers
- `user_subscriptions` - Active user subscriptions

## 🔒 Security Features

- Laravel Sanctum for API authentication
- Role-based access control
- Input validation and sanitization
- SQL injection protection
- CORS configuration
- Rate limiting (60 requests/minute)
- Password hashing with Bcrypt

## 💳 Payment Integration

### M-Pesa Integration
- STK Push for mobile payments
- Callback handling for payment confirmation
- Support for booking payments and listing fees
- Transaction tracking and refund capabilities

## 🎯 API Endpoints Overview

### Public Routes
- `POST /auth/register` - User registration
- `POST /auth/login` - User login
- `GET /cars` - Browse cars with filters
- `GET /cars/{id}` - Car details
- `GET /pricing-plans` - Available plans

### Protected Routes (Authenticated)
- User profile management
- Car CRUD (hosts only)
- Booking management
- Review system
- Payment processing
- Subscription management

### Admin Routes
- User management and verification
- Car approval workflow
- Platform analytics
- System administration

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter CarTest

# Generate coverage report
php artisan test --coverage
```

### Test Environment
- Uses SQLite in-memory database
- Factory classes for test data
- Feature and unit tests included

## 📦 Deployment

### Production Setup
1. Set environment variables for production
2. Configure database connection
3. Run migrations: `php artisan migrate --force`
4. Optimize application: `php artisan optimize --force`
5. Set up queue worker if needed
6. Configure web server (Nginx/Apache)

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_DATABASE=carmotii
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Commit Message Guidelines
- Use present tense: "Add feature" not "Added feature"
- Be descriptive: "Implement M-Pesa payment integration"
- Reference issues: "Fix #123 - User registration validation"

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Email: support@carmotii.com
- Documentation: [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

## 🔄 Version History

### v1.0.0 (2024-04-04)
- ✅ Complete authentication system
- ✅ Car management with images
- ✅ Booking system with availability
- ✅ Review and rating system
- ✅ M-Pesa payment integration
- ✅ Pricing plans and subscriptions
- ✅ Admin management tools
- ✅ API documentation

---

**Built with ❤️ using Laravel 13**
