<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required|exists:cars,id',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'nullable|array',
            'dropoff_location' => 'nullable|array',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $car = Car::findOrFail($request->car_id);

        // Check if car is available
        if (!$car->is_available || $car->status !== 'approved') {
            return response()->json([
                'message' => 'This car is not available for booking',
            ], 422);
        }

        // Check if car is available for the requested dates
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if (!$car->isAvailableForDates($startDate, $endDate)) {
            return response()->json([
                'message' => 'This car is already booked for the selected dates',
            ], 422);
        }

        // Calculate duration and pricing
        $durationDays = $startDate->diffInDays($endDate) + 1;
        $dailyRate = $car->daily_price;
        $totalAmount = $dailyRate * $durationDays;
        $securityDeposit = $dailyRate * 2; // 2 days as security deposit

        // Check user subscription limits
        $user = $request->user();
        if ($user->subscription && !$user->subscription->canAcceptMoreBookings()) {
            return response()->json([
                'message' => 'You have reached your monthly booking limit. Please upgrade your plan.',
            ], 403);
        }

        // Create booking
        $booking = $user->bookings()->create([
            'car_id' => $car->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => $durationDays,
            'total_amount' => $totalAmount,
            'daily_rate' => $dailyRate,
            'security_deposit' => $securityDeposit,
            'pickup_location' => $request->pickup_location,
            'dropoff_location' => $request->dropoff_location,
            'special_requests' => $request->special_requests,
        ]);

        // Update car booking count
        $car->increment('booking_count');

        return response()->json([
            'message' => 'Booking created successfully. Please complete payment to confirm.',
            'booking' => [
                'id' => $booking->id,
                'car' => [
                    'id' => $car->id,
                    'name' => $car->full_name,
                    'image' => $car->primary_image_url,
                ],
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'duration_days' => $booking->duration_days,
                'daily_rate' => $booking->daily_rate,
                'total_amount' => $booking->total_amount,
                'security_deposit' => $booking->security_deposit,
                'status' => $booking->status,
            ],
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id && 
            $booking->car->user_id !== $request->user()->id && 
            !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load(['car.images', 'car.user', 'user', 'payment']);

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'car' => [
                    'id' => $booking->car->id,
                    'name' => $booking->car->full_name,
                    'image' => $booking->car->primary_image_url,
                    'user' => [
                        'name' => $booking->car->user->full_name,
                        'phone' => $booking->car->user->phone_number,
                    ],
                ],
                'user' => [
                    'id' => $booking->user->id,
                    'name' => $booking->user->full_name,
                    'phone' => $booking->user->phone_number,
                ],
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'duration_days' => $booking->duration_days,
                'daily_rate' => $booking->daily_rate,
                'total_amount' => $booking->total_amount,
                'security_deposit' => $booking->security_deposit,
                'pickup_location' => $booking->pickup_location,
                'dropoff_location' => $booking->dropoff_location,
                'special_requests' => $booking->special_requests,
                'status' => $booking->status,
                'created_at' => $booking->created_at,
                'payment' => $booking->payment ? [
                    'status' => $booking->payment->status,
                    'method' => $booking->payment->method,
                    'amount' => $booking->payment->amount,
                ] : null,
            ],
        ]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'message' => 'This booking cannot be cancelled',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        // Process refund if payment was made
        if ($booking->payment && $booking->payment->status === 'completed') {
            // Create refund logic here
            // This would integrate with your payment processor
        }

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking,
        ]);
    }

    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        // Only car owner can confirm
        if ($booking->car->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking cannot be confirmed',
            ], 422);
        }

        // Check if payment is completed
        if (!$booking->payment || $booking->payment->status !== 'completed') {
            return response()->json([
                'message' => 'Booking must be paid before confirmation',
            ], 422);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Booking confirmed successfully',
            'booking' => $booking,
        ]);
    }

    public function complete(Request $request, Booking $booking): JsonResponse
    {
        // Only car owner can complete
        if ($booking->car->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'confirmed' && $booking->status !== 'active') {
            return response()->json([
                'message' => 'Booking cannot be completed',
            ], 422);
        }

        if ($booking->end_date->greaterThan(now())) {
            return response()->json([
                'message' => 'Booking period has not ended yet',
            ], 422);
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Booking completed successfully',
            'booking' => $booking,
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $query = Booking::with(['car', 'user']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json([
            'bookings' => $bookings,
        ]);
    }
}
