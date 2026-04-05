<?php

namespace App\Services;

use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'new_this_month' => User::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                    'verified' => User::where('verification_status', 'verified')->count(),
                    'banned' => User::where('is_banned', true)->count(),
                ],
                'cars' => [
                    'total' => Car::count(),
                    'approved' => Car::where('status', 'approved')->count(),
                    'pending' => Car::where('status', 'pending')->count(),
                    'available' => Car::where('availability_status', 'available')
                        ->where('status', 'approved')
                        ->count(),
                    'new_this_month' => Car::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'pending' => Booking::where('status', 'pending')->count(),
                    'confirmed' => Booking::where('status', 'confirmed')->count(),
                    'active' => Booking::where('status', 'active')->count(),
                    'completed' => Booking::where('status', 'completed')->count(),
                    'cancelled' => Booking::where('status', 'cancelled')->count(),
                    'new_this_month' => Booking::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
                'revenue' => [
                    'total' => Payment::where('status', 'completed')->sum('amount'),
                    'this_month' => Payment::where('status', 'completed')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('amount'),
                    'last_month' => Payment::where('status', 'completed')
                        ->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year)
                        ->sum('amount'),
                ],
                'reviews' => [
                    'total' => Review::count(),
                    'pending' => Review::where('status', 'pending')->count(),
                    'approved' => Review::where('status', 'approved')->count(),
                    'average_rating' => Review::where('status', 'approved')->avg('rating') ?? 0,
                    'new_this_month' => Review::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
            ];

            // Calculate growth percentages
            $stats['growth'] = [
                'users' => $this->calculateGrowth('users'),
                'cars' => $this->calculateGrowth('cars'),
                'bookings' => $this->calculateGrowth('bookings'),
                'revenue' => $this->calculateGrowth('revenue'),
            ];

            return $stats;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(string $period = 'monthly'): array
    {
        try {
            $query = Payment::where('status', 'completed');

            switch ($period) {
                case 'daily':
                    return $this->getDailyRevenue($query);
                case 'weekly':
                    return $this->getWeeklyRevenue($query);
                case 'yearly':
                    return $this->getYearlyRevenue($query);
                default:
                    return $this->getMonthlyRevenue($query);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user analytics
     */
    public function getUserAnalytics(string $period = 'monthly'): array
    {
        try {
            $query = User::query();

            switch ($period) {
                case 'daily':
                    return $this->getDailyUsers($query);
                case 'weekly':
                    return $this->getWeeklyUsers($query);
                case 'yearly':
                    return $this->getYearlyUsers($query);
                default:
                    return $this->getMonthlyUsers($query);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get booking analytics
     */
    public function getBookingAnalytics(string $period = 'monthly'): array
    {
        try {
            $query = Booking::query();

            switch ($period) {
                case 'daily':
                    return $this->getDailyBookings($query);
                case 'weekly':
                    return $this->getWeeklyBookings($query);
                case 'yearly':
                    return $this->getYearlyBookings($query);
                default:
                    return $this->getMonthlyBookings($query);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get car performance analytics
     */
    public function getCarPerformanceAnalytics(): array
    {
        try {
            $cars = Car::with(['bookings', 'reviews'])
                ->where('status', 'approved')
                ->get();

            $performance = [];
            foreach ($cars as $car) {
                $performance[] = [
                    'car' => [
                        'id' => $car->id,
                        'make' => $car->make,
                        'model' => $car->model,
                        'owner' => $car->user->first_name . ' ' . $car->user->last_name,
                    ],
                    'bookings' => [
                        'total' => $car->bookings->count(),
                        'completed' => $car->bookings->where('status', 'completed')->count(),
                        'cancelled' => $car->bookings->where('status', 'cancelled')->count(),
                        'revenue' => $car->bookings->where('status', 'completed')->sum('total_amount'),
                        'occupancy_rate' => $this->calculateOccupancyRate($car),
                    ],
                    'reviews' => [
                        'total' => $car->reviews->count(),
                        'average_rating' => $car->reviews->avg('rating') ?? 0,
                        'rating_distribution' => [
                            5 => $car->reviews->where('rating', 5)->count(),
                            4 => $car->reviews->where('rating', 4)->count(),
                            3 => $car->reviews->where('rating', 3)->count(),
                            2 => $car->reviews->where('rating', 2)->count(),
                            1 => $car->reviews->where('rating', 1)->count(),
                        ],
                    ],
                ];
            }

            // Sort by revenue
            usort($performance, function ($a, $b) {
                return $b['bookings']['revenue'] <=> $a['bookings']['revenue'];
            });

            return $performance;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get monthly revenue data
     */
    private function getMonthlyRevenue($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(amount) as revenue
            ')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get daily revenue data
     */
    private function getDailyRevenue($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d") as day,
                SUM(amount) as revenue
            ')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'day' => $item->day,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get weekly revenue data
     */
    private function getWeeklyRevenue($query): array
    {
        return $query->selectRaw('
                YEARWEEK(created_at) as week,
                SUM(amount) as revenue
            ')
            ->whereDate('created_at', '>=', now()->subMonths(3))
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'week' => $item->week,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get yearly revenue data
     */
    private function getYearlyRevenue($query): array
    {
        return $query->selectRaw('
                YEAR(created_at) as year,
                SUM(amount) as revenue
            ')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get monthly users data
     */
    private function getMonthlyUsers($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as users
            ')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'users' => (int) $item->users,
                ];
            })
            ->toArray();
    }

    /**
     * Get daily users data
     */
    private function getDailyUsers($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d") as day,
                COUNT(*) as users
            ')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'day' => $item->day,
                    'users' => (int) $item->users,
                ];
            })
            ->toArray();
    }

    /**
     * Get weekly users data
     */
    private function getWeeklyUsers($query): array
    {
        return $query->selectRaw('
                YEARWEEK(created_at) as week,
                COUNT(*) as users
            ')
            ->whereDate('created_at', '>=', now()->subMonths(3))
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'week' => $item->week,
                    'users' => (int) $item->users,
                ];
            })
            ->toArray();
    }

    /**
     * Get yearly users data
     */
    private function getYearlyUsers($query): array
    {
        return $query->selectRaw('
                YEAR(created_at) as year,
                COUNT(*) as users
            ')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'users' => (int) $item->users,
                ];
            })
            ->toArray();
    }

    /**
     * Get monthly bookings data
     */
    private function getMonthlyBookings($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as bookings,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            ')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'bookings' => (int) $item->bookings,
                    'completed' => (int) $item->completed,
                ];
            })
            ->toArray();
    }

    /**
     * Get daily bookings data
     */
    private function getDailyBookings($query): array
    {
        return $query->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d") as day,
                COUNT(*) as bookings,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            ')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'day' => $item->day,
                    'bookings' => (int) $item->bookings,
                    'completed' => (int) $item->completed,
                ];
            })
            ->toArray();
    }

    /**
     * Get weekly bookings data
     */
    private function getWeeklyBookings($query): array
    {
        return $query->selectRaw('
                YEARWEEK(created_at) as week,
                COUNT(*) as bookings,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            ')
            ->whereDate('created_at', '>=', now()->subMonths(3))
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'week' => $item->week,
                    'bookings' => (int) $item->bookings,
                    'completed' => (int) $item->completed,
                ];
            })
            ->toArray();
    }

    /**
     * Get yearly bookings data
     */
    private function getYearlyBookings($query): array
    {
        return $query->selectRaw('
                YEAR(created_at) as year,
                COUNT(*) as bookings,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed
            ')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'bookings' => (int) $item->bookings,
                    'completed' => (int) $item->completed,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth(string $metric): float
    {
        try {
            $current = $this->getCurrentPeriodValue($metric);
            $previous = $this->getPreviousPeriodValue($metric);

            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }

            return (($current - $previous) / $previous) * 100;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current period value
     */
    private function getCurrentPeriodValue(string $metric): float
    {
        switch ($metric) {
            case 'users':
                return User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
            case 'cars':
                return Car::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
            case 'bookings':
                return Booking::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count();
            case 'revenue':
                return Payment::where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount');
            default:
                return 0;
        }
    }

    /**
     * Get previous period value
     */
    private function getPreviousPeriodValue(string $metric): float
    {
        $previousMonth = now()->subMonth();
        
        switch ($metric) {
            case 'users':
                return User::whereMonth('created_at', $previousMonth->month)
                    ->whereYear('created_at', $previousMonth->year)
                    ->count();
            case 'cars':
                return Car::whereMonth('created_at', $previousMonth->month)
                    ->whereYear('created_at', $previousMonth->year)
                    ->count();
            case 'bookings':
                return Booking::whereMonth('created_at', $previousMonth->month)
                    ->whereYear('created_at', $previousMonth->year)
                    ->count();
            case 'revenue':
                return Payment::where('status', 'completed')
                    ->whereMonth('created_at', $previousMonth->month)
                    ->whereYear('created_at', $previousMonth->year)
                    ->sum('amount');
            default:
                return 0;
        }
    }

    /**
     * Calculate occupancy rate for a car
     */
    private function calculateOccupancyRate(Car $car): float
    {
        $totalDays = $car->created_at->diffInDays(now());
        $bookedDays = $car->bookings()
            ->where('status', 'completed')
            ->sum('duration_days');

        return $totalDays > 0 ? ($bookedDays / $totalDays) * 100 : 0;
    }

    /**
     * Get top performing cars
     */
    public function getTopPerformingCars(int $limit = 10): array
    {
        try {
            return Car::with(['user', 'bookings', 'reviews'])
                ->where('status', 'approved')
                ->get()
                ->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'make' => $car->make,
                        'model' => $car->model,
                        'owner' => $car->user->first_name . ' ' . $car->user->last_name,
                        'revenue' => $car->bookings->where('status', 'completed')->sum('total_amount'),
                        'bookings' => $car->bookings->where('status', 'completed')->count(),
                        'average_rating' => $car->reviews->avg('rating') ?? 0,
                    ];
                })
                ->sortByDesc('revenue')
                ->take($limit)
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
