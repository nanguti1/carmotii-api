<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Car;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /**
     * Create a new review
     */
    public function create(array $data, User $user): Review
    {
        try {
            $validator = Validator::make($data, [
                'car_id' => 'required|exists:cars,id',
                'booking_id' => 'required|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $car = Car::findOrFail($data['car_id']);
            $booking = Booking::findOrFail($data['booking_id']);

            // Check if user has completed the booking
            if ($booking->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'booking' => ['You can only review your own bookings.'],
                ]);
            }

            // Check if booking is completed
            if ($booking->status !== 'completed') {
                throw ValidationException::withMessages([
                    'booking' => ['You can only review completed bookings.'],
                ]);
            }

            // Check if review already exists for this booking
            $existingReview = Review::where('booking_id', $booking->id)->first();
            if ($existingReview) {
                throw ValidationException::withMessages([
                    'booking' => ['You have already reviewed this booking.'],
                ]);
            }

            // Check if booking is for the correct car
            if ($booking->car_id !== $car->id) {
                throw ValidationException::withMessages([
                    'booking' => ['This booking is not for the specified car.'],
                ]);
            }

            // Create review
            $review = Review::create([
                'user_id' => $user->id,
                'car_id' => $car->id,
                'booking_id' => $booking->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'status' => 'pending', // Requires moderation
            ]);

            return $review->load(['user', 'car', 'booking']);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update a review
     */
    public function update(Review $review, array $data, User $user): Review
    {
        try {
            // Check if user owns the review or is admin
            if ($review->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'review' => ['You are not authorized to update this review.'],
                ]);
            }

            $validator = Validator::make($data, [
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'comment' => 'sometimes|required|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $review->update($data);

            return $review->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a review
     */
    public function delete(Review $review, User $user): bool
    {
        try {
            // Check if user owns the review or is admin
            if ($review->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'review' => ['You are not authorized to delete this review.'],
                ]);
            }

            return $review->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get reviews for a car
     */
    public function getCarReviews(Car $car): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Review::with(['user'])
                ->where('car_id', $car->id)
                ->where('status', 'approved') // Only show approved reviews
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all reviews for admin (including pending ones)
     */
    public function getAllReviews(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Review::with(['user', 'car', 'booking'])
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Approve a review
     */
    public function approveReview(Review $review): Review
    {
        try {
            $review->update(['status' => 'approved']);
            return $review->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Reject a review
     */
    public function rejectReview(Review $review, string $reason = null): Review
    {
        try {
            $review->update([
                'status' => 'rejected',
                'moderation_notes' => $reason,
            ]);
            return $review->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get reviews by status
     */
    public function getReviewsByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Review::with(['user', 'car', 'booking'])
                ->where('status', $status)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get car rating statistics
     */
    public function getCarRatingStats(Car $car): array
    {
        try {
            $reviews = Review::where('car_id', $car->id)
                ->where('status', 'approved')
                ->get();

            $stats = [
                'average_rating' => $reviews->avg('rating') ?? 0,
                'total_reviews' => $reviews->count(),
                'rating_distribution' => [
                    5 => $reviews->where('rating', 5)->count(),
                    4 => $reviews->where('rating', 4)->count(),
                    3 => $reviews->where('rating', 3)->count(),
                    2 => $reviews->where('rating', 2)->count(),
                    1 => $reviews->where('rating', 1)->count(),
                ],
            ];

            return $stats;
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
}
