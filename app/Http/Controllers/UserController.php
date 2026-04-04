<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->verification_status) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->is_banned !== null) {
            $query->where('is_banned', $request->is_banned);
        }

        $users = $query->paginate(20);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function userCars(Request $request): JsonResponse
    {
        $cars = $request->user()
            ->cars()
            ->with(['images'])
            ->withCount(['bookings' => function ($query) {
                $query->whereIn('status', ['confirmed', 'active', 'completed']);
            }])
            ->latest()
            ->paginate(10);

        return response()->json([
            'cars' => $cars->map(function ($car) {
                return [
                    'id' => $car->id,
                    'name' => $car->full_name,
                    'type' => $car->type,
                    'location' => $car->location_city,
                    'daily_price' => $car->daily_price,
                    'status' => $car->status,
                    'is_available' => $car->is_available,
                    'rating' => $car->rating_average,
                    'booking_count' => $car->bookings_count ?? 0,
                    'image' => $car->primary_image_url,
                    'created_at' => $car->created_at,
                ];
            }),
        ]);
    }

    public function userBookings(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['car.images', 'car.user'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'bookings' => $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'car' => [
                        'id' => $booking->car->id,
                        'name' => $booking->car->full_name,
                        'image' => $booking->car->primary_image_url,
                        'owner' => $booking->car->user->full_name,
                    ],
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'duration_days' => $booking->duration_days,
                    'total_amount' => $booking->total_amount,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ];
            }),
        ]);
    }

    public function userReviews(Request $request): JsonResponse
    {
        $reviews = $request->user()
            ->reviews()
            ->with(['car', 'booking'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'reviews' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'status' => $review->status,
                    'car' => [
                        'id' => $review->car->id,
                        'name' => $review->car->full_name,
                    ],
                    'booking' => [
                        'id' => $review->booking->id,
                        'start_date' => $review->booking->start_date,
                    ],
                    'created_at' => $review->created_at,
                ];
            }),
        ]);
    }

    public function verifyUser(Request $request, User $user): JsonResponse
    {
        $validator = validator(request()->all(), [
            'verification_status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:verification_status,rejected|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'verification_status' => $request->verification_status,
        ]);

        return response()->json([
            'message' => "User verification status updated to {$request->verification_status}",
            'user' => $user,
        ]);
    }

    public function banUser(Request $request, User $user): JsonResponse
    {
        $validator = validator(request()->all(), [
            'is_banned' => 'required|boolean',
            'ban_reason' => 'required_if:is_banned,true|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'is_banned' => $request->is_banned,
        ]);

        // Revoke all tokens if banned
        if ($request->is_banned) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $request->is_banned ? 'User banned successfully' : 'User unbanned successfully',
            'user' => $user,
        ]);
    }
}
