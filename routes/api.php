<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PricingPlanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Public car browsing
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{car}', [CarController::class, 'show']);
Route::get('/cars/{car}/reviews', [ReviewController::class, 'carReviews']);

// Public pricing plans
Route::get('/pricing-plans', [PricingPlanController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    
    // User management
    Route::get('/user/cars', [UserController::class, 'userCars']);
    Route::get('/user/bookings', [UserController::class, 'userBookings']);
    Route::get('/user/reviews', [UserController::class, 'userReviews']);
    
    // Car management (hosts only)
    Route::middleware('role:host')->group(function () {
        Route::post('/cars', [CarController::class, 'store']);
        Route::put('/cars/{car}', [CarController::class, 'update']);
        Route::delete('/cars/{car}', [CarController::class, 'destroy']);
        Route::post('/cars/{car}/images', [CarController::class, 'uploadImages']);
        Route::delete('/cars/{car}/images/{image}', [CarController::class, 'deleteImage']);
        Route::put('/cars/{car}/availability', [CarController::class, 'updateAvailability']);
    });
    
    // Booking management
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::put('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::put('/bookings/{booking}/complete', [BookingController::class, 'complete']);
    
    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    
    // Payments
    Route::post('/payments/mpesa/initiate', [PaymentController::class, 'initiateMpesaPayment']);
    Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    
    // Pricing plan subscriptions
    Route::post('/subscribe', [PricingPlanController::class, 'subscribe']);
    Route::get('/subscription', [PricingPlanController::class, 'currentSubscription']);
});

// Admin routes
Route::middleware('auth:sanctum', 'role:admin')->prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{user}/verify', [UserController::class, 'verifyUser']);
    Route::put('/users/{user}/ban', [UserController::class, 'banUser']);
    Route::get('/bookings', [BookingController::class, 'adminIndex']);
    Route::get('/cars/pending', [CarController::class, 'pendingCars']);
    Route::put('/cars/{car}/approve', [CarController::class, 'approveCar']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/revenue', [AnalyticsController::class, 'revenue']);
    Route::get('/analytics/users', [AnalyticsController::class, 'users']);
    Route::get('/analytics/bookings', [AnalyticsController::class, 'bookings']);
    Route::get('/analytics/car-performance', [AnalyticsController::class, 'carPerformance']);
    Route::get('/analytics/top-cars', [AnalyticsController::class, 'topCars']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports/generate', [ReportController::class, 'generate']);
    Route::post('/reports/download', [ReportController::class, 'download']);
    Route::get('/reports/download-file/{filepath}', [ReportController::class, 'downloadFile']);
});
