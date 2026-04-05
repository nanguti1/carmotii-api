<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getDashboardStats();

            return response()->json([
                'analytics' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function revenue(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly');
            $revenue = $this->analyticsService->getRevenueAnalytics($period);

            return response()->json([
                'revenue_analytics' => $revenue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch revenue analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function users(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly');
            $users = $this->analyticsService->getUserAnalytics($period);

            return response()->json([
                'user_analytics' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch user analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bookings(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'monthly');
            $bookings = $this->analyticsService->getBookingAnalytics($period);

            return response()->json([
                'booking_analytics' => $bookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch booking analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function carPerformance(): JsonResponse
    {
        try {
            $performance = $this->analyticsService->getCarPerformanceAnalytics();

            return response()->json([
                'car_performance' => $performance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch car performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function topCars(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $cars = $this->analyticsService->getTopPerformingCars($limit);

            return response()->json([
                'top_performing_cars' => $cars
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch top performing cars',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
