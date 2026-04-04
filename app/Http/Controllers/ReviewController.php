<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::findOrFail($request->booking_id);

        // Check if user owns this booking
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if booking is completed
        if (!$booking->canBeReviewed()) {
            return response()->json([
                'message' => 'You can only review completed bookings',
            ], 422);
        }

        // Check if review already exists
        if ($booking->review) {
            return response()->json([
                'message' => 'You have already reviewed this booking',
            ], 422);
        }

        $review = $booking->car->reviews()->create([
            'user_id' => $request->user()->id,
            'booking_id' => $booking->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending', // Reviews need moderation
        ]);

        return response()->json([
            'message' => 'Review submitted successfully. It will be visible after moderation.',
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'created_at' => $review->created_at,
            ],
        ], 201);
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($review->status === 'approved') {
            return response()->json([
                'message' => 'Approved reviews cannot be modified',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|required|integer|min:1|max:5',
            'comment' => 'sometimes|required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $review->update($request->only(['rating', 'comment']));
        $review->update(['status' => 'pending']); // Reset to pending for moderation

        return response()->json([
            'message' => 'Review updated successfully. It will be visible after moderation.',
            'review' => $review,
        ]);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ]);
    }

    public function carReviews(Request $request, $carId): JsonResponse
    {
        $reviews = Review::where('car_id', $carId)
            ->where('status', 'approved')
            ->with(['user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'profile_image');
            }])
            ->latest()
            ->paginate(10);

        return response()->json([
            'reviews' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => [
                        'name' => $review->user->full_name,
                        'image' => $review->user->profile_image_url,
                    ],
                    'created_at' => $review->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }
}
