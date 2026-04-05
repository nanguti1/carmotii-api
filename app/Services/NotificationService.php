<?php

namespace App\Services;

use App\Models\User;
use App\Models\Car;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingNotification;
use App\Mail\PaymentNotification;
use App\Mail\UserRegistrationNotification;

class NotificationService
{
    /**
     * Send booking notification to car owner
     */
    public function sendBookingNotification(Booking $booking): void
    {
        try {
            $carOwner = $booking->car->user;
            $customer = $booking->user;

            // Send email to car owner
            Mail::to($carOwner->email)->send(new BookingNotification(
                $carOwner,
                $booking,
                'new_booking'
            ));

            // Create in-app notification
            $this->createInAppNotification($carOwner, [
                'type' => 'booking',
                'title' => 'New Booking Received',
                'message' => "{$customer->first_name} {$customer->last_name} booked your {$booking->car->make} {$booking->car->model}",
                'data' => [
                    'booking_id' => $booking->id,
                    'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                    'car_name' => $booking->car->make . ' ' . $booking->car->model,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send payment confirmation notification
     */
    public function sendPaymentNotification(Payment $payment): void
    {
        try {
            $user = $payment->user;

            // Send email confirmation
            Mail::to($user->email)->send(new PaymentNotification(
                $user,
                $payment,
                'payment_success'
            ));

            // Create in-app notification
            $this->createInAppNotification($user, [
                'type' => 'payment',
                'title' => 'Payment Successful',
                'message' => "Your payment of KES {$payment->amount} has been processed successfully",
                'data' => [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'transaction_id' => $payment->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send booking status update notification
     */
    public function sendBookingStatusNotification(Booking $booking, string $status): void
    {
        try {
            $customer = $booking->user;

            $statusMessages = [
                'confirmed' => 'Your booking has been confirmed',
                'cancelled' => 'Your booking has been cancelled',
                'completed' => 'Your booking has been completed',
            ];

            $title = $status === 'confirmed' ? 'Booking Confirmed' :
                     ($status === 'cancelled' ? 'Booking Cancelled' : 'Booking Completed');

            // Create in-app notification
            $this->createInAppNotification($customer, [
                'type' => 'booking_status',
                'title' => $title,
                'message' => $statusMessages[$status] ?? 'Your booking status has been updated',
                'data' => [
                    'booking_id' => $booking->id,
                    'status' => $status,
                    'car_name' => $booking->car->make . ' ' . $booking->car->model,
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send car approval notification to owner
     */
    public function sendCarApprovalNotification(Car $car, string $status): void
    {
        try {
            $carOwner = $car->user;

            $title = $status === 'approved' ? 'Car Approved' : 'Car Rejected';
            $message = $status === 'approved' ? 
                "Your {$car->make} {$car->model} has been approved and is now live" :
                "Your {$car->make} {$car->model} has been rejected. Reason: {$car->rejection_reason}";

            // Create in-app notification
            $this->createInAppNotification($carOwner, [
                'type' => 'car_status',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'car_id' => $car->id,
                    'status' => $status,
                    'rejection_reason' => $car->rejection_reason,
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send user verification notification
     */
    public function sendUserVerificationNotification(User $user, string $status): void
    {
        try {
            $title = $status === 'verified' ? 'Account Verified' : 'Account Suspended';
            $message = $status === 'verified' ? 
                "Your account has been verified successfully" :
                "Your account has been suspended";

            // Create in-app notification
            $this->createInAppNotification($user, [
                'type' => 'account_status',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'verification_status' => $status,
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create in-app notification
     */
    private function createInAppNotification(User $user, array $data): void
    {
        // In a real implementation, you would save to a notifications table
        // For now, we'll simulate this with a log
        \Log::info("Notification created for user {$user->id}: " . json_encode($data));
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, int $limit = 20): array
    {
        try {
            // In a real implementation, fetch from database
            // For now, return mock data
            return [
                [
                    'id' => 1,
                    'type' => 'booking',
                    'title' => 'New Booking Received',
                    'message' => 'John Doe booked your Toyota Camry',
                    'data' => [
                        'booking_id' => 123,
                        'customer_name' => 'John Doe',
                        'car_name' => 'Toyota Camry',
                    ],
                    'is_read' => false,
                    'created_at' => now()->subMinutes(30),
                ],
                [
                    'id' => 2,
                    'type' => 'payment',
                    'title' => 'Payment Successful',
                    'message' => 'Your payment of KES 4500 has been processed successfully',
                    'data' => [
                        'payment_id' => 456,
                        'amount' => 4500,
                    ],
                    'is_read' => false,
                    'created_at' => now()->subHours(2),
                ],
                [
                    'id' => 3,
                    'type' => 'booking_status',
                    'title' => 'Booking Confirmed',
                    'message' => 'Your booking has been confirmed',
                    'data' => [
                        'booking_id' => 789,
                        'status' => 'confirmed',
                    ],
                    'is_read' => true,
                    'created_at' => now()->subDay(),
                ],
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(User $user, int $notificationId): bool
    {
        try {
            // In a real implementation, update notification in database
            \Log::info("Notification {$notificationId} marked as read by user {$user->id}");
            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(User $user): int
    {
        try {
            // In a real implementation, count unread notifications
            return 2; // Mock count
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Send system notifications
     */
    public function sendSystemNotification(array $data): void
    {
        try {
            // Send to all users or specific role
            $users = User::where('is_active', true)->get();

            foreach ($users as $user) {
                $this->createInAppNotification($user, [
                    'type' => 'system',
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'data' => $data['data'] ?? [],
                ]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
