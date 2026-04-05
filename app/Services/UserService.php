<?php

namespace App\Services;

use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get all users for admin
     */
    public function getAllUsers(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return User::orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user's cars
     */
    public function getUserCars(User $user): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Car::with(['images'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user's bookings
     */
    public function getUserBookings(User $user): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Booking::with(['car'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user's reviews
     */
    public function getUserReviews(User $user): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Review::with(['car'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Verify user
     */
    public function verifyUser(User $user): User
    {
        try {
            $user->update(['verification_status' => 'verified']);
            return $user->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Ban/unban user
     */
    public function banUser(User $user, bool $banned = true): User
    {
        try {
            $user->update(['is_banned' => $banned]);
            return $user->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Deactivate/activate user
     */
    public function toggleUserStatus(User $user, bool $active): User
    {
        try {
            $user->update(['is_active' => $active]);
            return $user->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(User $user): array
    {
        try {
            $stats = [
                'total_cars' => Car::where('user_id', $user->id)->count(),
                'active_cars' => Car::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('availability_status', 'available')
                    ->count(),
                'total_bookings' => Booking::where('user_id', $user->id)->count(),
                'completed_bookings' => Booking::where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->count(),
                'total_reviews' => Review::where('user_id', $user->id)->count(),
                'average_rating' => Review::where('user_id', $user->id)
                    ->avg('rating') ?? 0,
            ];

            // If user is a host, add host-specific stats
            if ($user->hasRole('host')) {
                $stats['total_earnings'] = Booking::whereHas('car', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('status', 'completed')->sum('total_amount');
                
                $stats['host_bookings'] = Booking::whereHas('car', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count();
            }

            return $stats;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Search users
     */
    public function searchUsers(string $query): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return User::where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('phone_number', 'like', "%{$query}%")
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get users by verification status
     */
    public function getUsersByVerificationStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return User::where('verification_status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return User::role($role)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
