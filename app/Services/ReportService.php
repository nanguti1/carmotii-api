<?php

namespace App\Services;

use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Generate monthly revenue report
     */
    public function generateRevenueReport(string $month = null): array
    {
        try {
            $targetMonth = $month ?? Carbon::now()->format('Y-m');
            
            $report = [
                'title' => 'Monthly Revenue Report',
                'period' => $targetMonth,
                'generated_at' => Carbon::now()->toDateTimeString(),
                'data' => [
                    'total_revenue' => $this->getMonthlyRevenue($targetMonth),
                    'booking_revenue' => $this->getBookingRevenue($targetMonth),
                    'subscription_revenue' => $this->getSubscriptionRevenue($targetMonth),
                    'payment_methods' => $this->getPaymentMethodBreakdown($targetMonth),
                    'daily_breakdown' => $this->getDailyRevenueBreakdown($targetMonth),
                ],
            ];

            return $report;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate user activity report
     */
    public function generateUserActivityReport(string $period = 'monthly'): array
    {
        try {
            $report = [
                'title' => 'User Activity Report',
                'period' => $period,
                'generated_at' => Carbon::now()->toDateTimeString(),
                'data' => [
                    'new_users' => $this->getNewUsers($period),
                    'active_users' => $this->getActiveUsers($period),
                    'user_retention' => $this->getUserRetention($period),
                    'user_demographics' => $this->getUserDemographics(),
                    'top_users' => $this->getTopUsers($period),
                ],
            ];

            return $report;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate booking summary report
     */
    public function generateBookingSummaryReport(string $period = 'monthly'): array
    {
        try {
            $report = [
                'title' => 'Booking Summary Report',
                'period' => $period,
                'generated_at' => Carbon::now()->toDateTimeString(),
                'data' => [
                    'total_bookings' => $this->getBookingStats($period, 'total'),
                    'completed_bookings' => $this->getBookingStats($period, 'completed'),
                    'cancelled_bookings' => $this->getBookingStats($period, 'cancelled'),
                    'revenue_per_booking' => $this->getAverageRevenuePerBooking($period),
                    'popular_cars' => $this->getPopularCars($period),
                    'booking_trends' => $this->getBookingTrends($period),
                ],
            ];

            return $report;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate car performance report
     */
    public function generateCarPerformanceReport(): array
    {
        try {
            $report = [
                'title' => 'Car Performance Report',
                'period' => 'all_time',
                'generated_at' => Carbon::now()->toDateTimeString(),
                'data' => [
                    'total_cars' => Car::count(),
                    'approved_cars' => Car::where('status', 'approved')->count(),
                    'average_rating' => Review::avg('rating') ?? 0,
                    'top_performing_cars' => $this->getTopPerformingCars(10),
                    'car_utilization' => $this->getCarUtilizationStats(),
                    'maintenance_issues' => $this->getMaintenanceStats(),
                ],
            ];

            return $report;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate financial summary report
     */
    public function generateFinancialSummaryReport(string $period = 'monthly'): array
    {
        try {
            $report = [
                'title' => 'Financial Summary Report',
                'period' => $period,
                'generated_at' => Carbon::now()->toDateTimeString(),
                'data' => [
                    'total_revenue' => $this->getTotalRevenue($period),
                    'total_expenses' => $this->getTotalExpenses($period),
                    'net_profit' => $this->getNetProfit($period),
                    'commission_earned' => $this->getCommissionEarned($period),
                    'payment_breakdown' => $this->getPaymentBreakdown($period),
                    'growth_metrics' => $this->getFinancialGrowthMetrics($period),
                ],
            ];

            return $report;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Export report to CSV
     */
    public function exportToCSV(array $reportData, string $filename): string
    {
        try {
            $csv = '';
            $headers = array_keys($reportData[0] ?? []);
            
            if (!empty($headers)) {
                $csv .= implode(',', $headers) . "\n";
            }

            foreach ($reportData as $row) {
                $csv .= implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }

            $filepath = "reports/{$filename}.csv";
            Storage::disk('local')->put($filepath, $csv);

            return $filepath;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get monthly revenue
     */
    private function getMonthlyRevenue(string $month): float
    {
        return Payment::where('status', 'completed')
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->sum('amount');
    }

    /**
     * Get booking revenue
     */
    private function getBookingRevenue(string $month): float
    {
        return Payment::where('status', 'completed')
            ->where('type', 'booking')
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->sum('amount');
    }

    /**
     * Get subscription revenue
     */
    private function getSubscriptionRevenue(string $month): float
    {
        return Payment::where('status', 'completed')
            ->where('type', 'subscription')
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->sum('amount');
    }

    /**
     * Get payment method breakdown
     */
    private function getPaymentMethodBreakdown(string $month): array
    {
        return Payment::where('status', 'completed')
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->method,
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                    'percentage' => 0, // Will be calculated
                ];
            })
            ->toArray();
    }

    /**
     * Get daily revenue breakdown
     */
    private function getDailyRevenueBreakdown(string $month): array
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        return Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as day, SUM(amount) as revenue')
            ->groupBy('day')
            ->orderBy('day')
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
     * Get new users
     */
    private function getNewUsers(string $period): int
    {
        $query = User::query();

        switch ($period) {
            case 'daily':
                return $query->whereDate('created_at', Carbon::today())->count();
            case 'weekly':
                return $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->count();
            case 'monthly':
                return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count();
            default:
                return $query->count();
        }
    }

    /**
     * Get active users
     */
    private function getActiveUsers(string $period): int
    {
        $query = User::where('is_active', true);

        switch ($period) {
            case 'daily':
                return $query->whereDate('last_login_at', Carbon::today())->count();
            case 'weekly':
                return $query->whereBetween('last_login_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->count();
            case 'monthly':
                return $query->whereMonth('last_login_at', Carbon::now()->month)
                    ->whereYear('last_login_at', Carbon::now()->year)
                    ->count();
            default:
                return $query->count();
        }
    }

    /**
     * Get user retention
     */
    private function getUserRetention(string $period): array
    {
        // Calculate retention based on user activity
        $totalUsers = User::count();
        $activeUsers = $this->getActiveUsers($period);
        
        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'retention_rate' => $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0,
        ];
    }

    /**
     * Get user demographics
     */
    private function getUserDemographics(): array
    {
        return [
            'by_role' => [
                'users' => User::role('user')->count(),
                'hosts' => User::role('host')->count(),
                'admins' => User::role('admin')->count(),
            ],
            'by_verification_status' => [
                'verified' => User::where('verification_status', 'verified')->count(),
                'pending' => User::where('verification_status', 'pending')->count(),
                'unverified' => User::where('verification_status', 'unverified')->count(),
            ],
            'by_registration_month' => User::selectRaw('
                    MONTH(created_at) as month,
                    COUNT(*) as count
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->limit(12)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Get top users
     */
    private function getTopUsers(string $period): array
    {
        return User::with(['cars', 'bookings'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'cars_listed' => $user->cars->count(),
                    'total_bookings' => $user->bookings->count(),
                    'revenue_generated' => $user->bookings->where('status', 'completed')->sum('total_amount'),
                ];
            })
            ->sortByDesc('revenue_generated')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get booking stats
     */
    private function getBookingStats(string $period, string $status): int
    {
        $query = Booking::query();

        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
                break;
        }

        switch ($status) {
            case 'completed':
                return $query->where('status', 'completed')->count();
            case 'cancelled':
                return $query->where('status', 'cancelled')->count();
            default:
                return $query->count();
        }
    }

    /**
     * Get average revenue per booking
     */
    private function getAverageRevenuePerBooking(string $period): float
    {
        $totalRevenue = $this->getTotalRevenue($period);
        $totalBookings = $this->getBookingStats($period, 'completed');

        return $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
    }

    /**
     * Get popular cars
     */
    private function getPopularCars(string $period): array
    {
        $query = Booking::with('car');

        switch ($period) {
            case 'daily':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
                break;
        }

        return $query->selectRaw('
                car_id,
                COUNT(*) as booking_count,
                car_make,
                car_model
            ')
            ->join('cars', 'cars.id', '=', 'bookings.car_id')
            ->groupBy('car_id', 'car_make', 'car_model')
            ->orderByDesc('booking_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'car_id' => $item->car_id,
                    'make' => $item->car_make,
                    'model' => $item->car_model,
                    'bookings' => (int) $item->booking_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get booking trends
     */
    private function getBookingTrends(string $period): array
    {
        // Return booking trends over time
        $query = Booking::query();

        switch ($period) {
            case 'daily':
                return $query->selectRaw('
                        DATE_FORMAT(created_at, "%Y-%m-%d") as date,
                        COUNT(*) as count
                    ')
                    ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->toArray();
            case 'weekly':
                return $query->selectRaw('
                        YEARWEEK(created_at) as week,
                        COUNT(*) as count
                    ')
                    ->whereDate('created_at', '>=', Carbon::now()->subMonths(3))
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get()
                    ->toArray();
            default:
                return $query->selectRaw('
                        DATE_FORMAT(created_at, "%Y-%m") as month,
                        COUNT(*) as count
                    ')
                    ->whereDate('created_at', '>=', Carbon::now()->subYear())
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->toArray();
        }
    }

    /**
     * Get top performing cars
     */
    private function getTopPerformingCars(int $limit): array
    {
        return Car::with(['user', 'bookings', 'reviews'])
            ->where('status', 'approved')
            ->get()
            ->map(function ($car) {
                return [
                    'id' => $car->id,
                    'make' => $car->make,
                    'model' => $car->model,
                    'owner' => $car->user->first_name . ' ' . $car->user->last_name,
                    'total_bookings' => $car->bookings->count(),
                    'completed_bookings' => $car->bookings->where('status', 'completed')->count(),
                    'total_revenue' => $car->bookings->where('status', 'completed')->sum('total_amount'),
                    'average_rating' => $car->reviews->avg('rating') ?? 0,
                ];
            })
            ->sortByDesc('total_revenue')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Get car utilization stats
     */
    private function getCarUtilizationStats(): array
    {
        $totalCars = Car::where('status', 'approved')->count();
        $availableCars = Car::where('status', 'approved')
            ->where('availability_status', 'available')
            ->count();

        return [
            'total_cars' => $totalCars,
            'available_cars' => $availableCars,
            'unavailable_cars' => $totalCars - $availableCars,
            'utilization_rate' => $totalCars > 0 ? ($availableCars / $totalCars) * 100 : 0,
        ];
    }

    /**
     * Get maintenance stats
     */
    private function getMaintenanceStats(): array
    {
        $totalCars = Car::count();
        $maintenanceCars = Car::where('availability_status', 'maintenance')->count();

        return [
            'total_cars' => $totalCars,
            'cars_in_maintenance' => $maintenanceCars,
            'maintenance_rate' => $totalCars > 0 ? ($maintenanceCars / $totalCars) * 100 : 0,
        ];
    }

    /**
     * Get total revenue
     */
    private function getTotalRevenue(string $period): float
    {
        $query = Payment::where('status', 'completed');

        switch ($period) {
            case 'daily':
                return $query->whereDate('created_at', Carbon::today())->sum('amount');
            case 'weekly':
                return $query->whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->sum('amount');
            case 'monthly':
                return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('amount');
            default:
                return $query->sum('amount');
        }
    }

    /**
     * Get total expenses
     */
    private function getTotalExpenses(string $period): float
    {
        // This would track platform expenses
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get net profit
     */
    private function getNetProfit(string $period): float
    {
        $revenue = $this->getTotalRevenue($period);
        $expenses = $this->getTotalExpenses($period);
        
        return $revenue - $expenses;
    }

    /**
     * Get commission earned
     */
    private function getCommissionEarned(string $period): float
    {
        $revenue = $this->getTotalRevenue($period);
        $commissionRate = 0.15; // 15% commission
        
        return $revenue * $commissionRate;
    }

    /**
     * Get payment breakdown
     */
    private function getPaymentBreakdown(string $period): array
    {
        return Payment::where('status', 'completed')
            ->selectRaw('
                    type,
                    SUM(amount) as total,
                    COUNT(*) as count
                ')
            ->where(function ($query) use ($period) {
                switch ($period) {
                    case 'daily':
                        $query->whereDate('created_at', Carbon::today());
                        break;
                    case 'weekly':
                        $query->whereBetween('created_at', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ]);
                        break;
                    case 'monthly':
                        $query->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year);
                        break;
                }
            })
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get financial growth metrics
     */
    private function getFinancialGrowthMetrics(string $period): array
    {
        $currentRevenue = $this->getTotalRevenue($period);
        $previousRevenue = $this->getPreviousPeriodRevenue($period);

        return [
            'current_period_revenue' => $currentRevenue,
            'previous_period_revenue' => $previousRevenue,
            'growth_rate' => $previousRevenue > 0 ? 
                (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0,
            'growth_amount' => $currentRevenue - $previousRevenue,
        ];
    }

    /**
     * Get previous period revenue
     */
    private function getPreviousPeriodRevenue(string $period): float
    {
        switch ($period) {
            case 'daily':
                return Payment::where('status', 'completed')
                    ->whereDate('created_at', Carbon::yesterday())
                    ->sum('amount');
            case 'weekly':
                return Payment::where('status', 'completed')
                    ->whereBetween('created_at', [
                        Carbon::now()->subWeek()->startOfWeek(),
                        Carbon::now()->subWeek()->endOfWeek()
                    ])
                    ->sum('amount');
            case 'monthly':
                return Payment::where('status', 'completed')
                    ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                    ->whereYear('created_at', Carbon::now()->subMonth()->year)
                    ->sum('amount');
            default:
                return 0;
        }
    }
}
