<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a new booking
     */
    public function create(array $data, User $user): Booking
    {
        try {
            $validator = Validator::make($data, [
                'car_id' => 'required|exists:cars,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $car = Car::findOrFail($data['car_id']);

            // Check if car is available
            if ($car->availability_status !== 'available') {
                throw ValidationException::withMessages([
                    'car' => ['This car is not available for booking.'],
                ]);
            }

            // Check if car is approved
            if ($car->status !== 'approved') {
                throw ValidationException::withMessages([
                    'car' => ['This car is not yet approved for booking.'],
                ]);
            }

            // Check for booking conflicts
            $conflict = $this->checkBookingConflict($car, $data['start_date'], $data['end_date']);
            if ($conflict) {
                throw ValidationException::withMessages([
                    'dates' => ['The selected dates are not available for this car.'],
                ]);
            }

            // Calculate duration and total amount
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $duration = $startDate->diffInDays($endDate) + 1; // Include both start and end dates
            $totalAmount = $duration * $car->daily_price;

            // Create booking
            $booking = Booking::create([
                'user_id' => $user->id,
                'car_id' => $car->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'duration_days' => $duration,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            return $booking->load(['car', 'user']);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check for booking conflicts
     */
    private function checkBookingConflict(Car $car, string $startDate, string $endDate): bool
    {
        $conflictingBookings = Booking::where('car_id', $car->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                });
            })
            ->exists();

        return $conflictingBookings;
    }

    /**
     * Cancel a booking
     */
    public function cancel(Booking $booking, User $user): Booking
    {
        try {
            // Check if user owns the booking or is admin
            if ($booking->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'booking' => ['You are not authorized to cancel this booking.'],
                ]);
            }

            // Check if booking can be cancelled
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                throw ValidationException::withMessages([
                    'booking' => ['This booking cannot be cancelled.'],
                ]);
            }

            // Check cancellation policy (e.g., can't cancel within 24 hours of start)
            $startDate = Carbon::parse($booking->start_date);
            $now = Carbon::now();
            $hoursDiff = $now->diffInHours($startDate, false);

            if ($hoursDiff < 24 && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'booking' => ['Bookings can only be cancelled at least 24 hours before start date.'],
                ]);
            }

            $booking->update(['status' => 'cancelled']);

            return $booking->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Confirm a booking
     */
    public function confirm(Booking $booking, User $user): Booking
    {
        try {
            // Check if user owns the car or is admin
            if ($booking->car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'booking' => ['You are not authorized to confirm this booking.'],
                ]);
            }

            if ($booking->status !== 'pending') {
                throw ValidationException::withMessages([
                    'booking' => ['Only pending bookings can be confirmed.'],
                ]);
            }

            $booking->update(['status' => 'confirmed']);

            return $booking->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Complete a booking
     */
    public function complete(Booking $booking, User $user): Booking
    {
        try {
            // Check if user owns the car or is admin
            if ($booking->car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'booking' => ['You are not authorized to complete this booking.'],
                ]);
            }

            if ($booking->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'booking' => ['Only confirmed bookings can be completed.'],
                ]);
            }

            // Check if booking end date has passed
            $endDate = Carbon::parse($booking->end_date);
            $now = Carbon::now();
            if ($now->lt($endDate)) {
                throw ValidationException::withMessages([
                    'booking' => ['Bookings can only be completed after the end date.'],
                ]);
            }

            $booking->update(['status' => 'completed']);

            return $booking->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all bookings for admin
     */
    public function getAllBookings(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Booking::with(['car', 'user'])
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
     * Get car owner's bookings
     */
    public function getCarOwnerBookings(User $user): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Booking::with(['user', 'car'])
                ->whereHas('car', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
