<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\UserSubscription;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initiate M-Pesa payment for booking
     */
    public function initiateMpesaPayment(array $data, User $user): array
    {
        try {
            $validator = Validator::make($data, [
                'booking_id' => 'required|exists:bookings,id',
                'phone_number' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $booking = Booking::findOrFail($data['booking_id']);

            // Check if user owns the booking
            if ($booking->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'booking' => ['You are not authorized to pay for this booking.'],
                ]);
            }

            // Check if booking is confirmed
            if ($booking->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'booking' => ['Payment can only be made for confirmed bookings.'],
                ]);
            }

            // Check if payment already exists
            $existingPayment = Payment::where('booking_id', $booking->id)
                ->where('status', 'completed')
                ->first();

            if ($existingPayment) {
                throw ValidationException::withMessages([
                    'booking' => ['This booking has already been paid for.'],
                ]);
            }

            // Generate transaction ID
            $transactionId = 'CAR' . strtoupper(Str::random(8));

            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'transaction_id' => $transactionId,
                'type' => 'booking',
                'method' => 'mpesa',
                'amount' => $booking->total_amount,
                'status' => 'pending',
                'phone_number' => $data['phone_number'],
                'metadata' => [
                    'booking_reference' => $booking->id,
                    'car_name' => $booking->car->make . ' ' . $booking->car->model,
                    'duration' => $booking->duration_days . ' days',
                ],
            ]);

            // Simulate M-Pesa STK Push (in real implementation, integrate with M-Pesa API)
            $stkPushResponse = $this->simulateMpesaStkPush($payment);

            return [
                'payment' => $payment,
                'stk_push_response' => $stkPushResponse,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Initiate M-Pesa payment for subscription
     */
    public function initiateSubscriptionPayment(array $data, User $user): array
    {
        try {
            $validator = Validator::make($data, [
                'pricing_plan_id' => 'required|exists:pricing_plans,id',
                'phone_number' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $pricingPlan = PricingPlan::findOrFail($data['pricing_plan_id']);

            // Check if user already has active subscription
            $activeSubscription = UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSubscription) {
                throw ValidationException::withMessages([
                    'subscription' => ['You already have an active subscription.'],
                ]);
            }

            // Generate transaction ID
            $transactionId = 'SUB' . strtoupper(Str::random(8));

            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $pricingPlan->id,
                'transaction_id' => $transactionId,
                'type' => 'subscription',
                'method' => 'mpesa',
                'amount' => $pricingPlan->price,
                'status' => 'pending',
                'phone_number' => $data['phone_number'],
                'metadata' => [
                    'plan_name' => $pricingPlan->name,
                    'billing_cycle' => $pricingPlan->billing_cycle,
                ],
            ]);

            // Simulate M-Pesa STK Push
            $stkPushResponse = $this->simulateMpesaStkPush($payment);

            return [
                'payment' => $payment,
                'stk_push_response' => $stkPushResponse,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function handleMpesaCallback(array $callbackData): Payment
    {
        try {
            $validator = Validator::make($callbackData, [
                'transaction_id' => 'required|string',
                'status' => 'required|string|in:success,failed',
                'mpesa_receipt' => 'required|string',
                'phone_number' => 'required|string',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $payment = Payment::where('transaction_id', $callbackData['transaction_id'])
                ->firstOrFail();

            if ($payment->status === 'completed') {
                throw ValidationException::withMessages([
                    'payment' => ['This payment has already been processed.'],
                ]);
            }

            // Update payment status
            $payment->update([
                'status' => $callbackData['status'] === 'success' ? 'completed' : 'failed',
                'mpesa_receipt' => $callbackData['mpesa_receipt'],
                'processed_at' => now(),
            ]);

            // If payment is successful, process the related item
            if ($payment->status === 'completed') {
                $this->processSuccessfulPayment($payment);
            }

            return $payment->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(Payment $payment): void
    {
        if ($payment->type === 'booking' && $payment->booking) {
            // Update booking status
            $payment->booking->update(['status' => 'confirmed']);
        } elseif ($payment->type === 'subscription' && $payment->pricingPlan) {
            // Create user subscription
            UserSubscription::create([
                'user_id' => $payment->user_id,
                'pricing_plan_id' => $payment->pricing_plan_id,
                'payment_id' => $payment->id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addMonths(1), // Default to 1 month
                'auto_renew' => true,
            ]);
        }
    }

    /**
     * Simulate M-Pesa STK Push (replace with actual M-Pesa API integration)
     */
    private function simulateMpesaStkPush(Payment $payment): array
    {
        // In real implementation, integrate with M-Pesa API here
        return [
            'success' => true,
            'checkout_request_id' => 'ws_CO_' . strtoupper(Str::random(12)),
            'merchant_request_id' => strtoupper(Str::random(10)),
            'response_description' => 'Success. Request accepted for processing',
            'customer_message' => 'Please enter your M-Pesa PIN to complete the transaction',
        ];
    }

    /**
     * Get payment details
     */
    public function getPayment(string $transactionId, User $user): Payment
    {
        try {
            $payment = Payment::where('transaction_id', $transactionId)
                ->firstOrFail();

            // Check if user owns the payment or is admin
            if ($payment->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'payment' => ['You are not authorized to view this payment.'],
                ]);
            }

            return $payment->load(['booking', 'pricingPlan']);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
