<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PricingPlan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function initiateMpesaPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:booking_payment,listing_fee',
            'phone_number' => 'required|string|regex:/^[254]\d{9}$/',
            'booking_id' => 'required_if:type,booking_payment|exists:bookings,id',
            'pricing_plan_id' => 'required_if:type,listing_fee|exists:pricing_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $amount = 0;
        $description = '';

        if ($request->type === 'booking_payment') {
            $booking = Booking::findOrFail($request->booking_id);
            
            if ($booking->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($booking->payment && $booking->payment->status === 'completed') {
                return response()->json([
                    'message' => 'Payment already completed for this booking',
                ], 422);
            }

            $amount = $booking->total_amount + $booking->security_deposit;
            $description = "Payment for booking #{$booking->id} - {$booking->car->full_name}";
        } else {
            $pricingPlan = PricingPlan::findOrFail($request->pricing_plan_id);
            $amount = $pricingPlan->price;
            $description = "Listing fee for {$pricingPlan->name} plan";
        }

        // Generate unique transaction ID
        $transactionId = 'CAR' . strtoupper(uniqid());

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'booking_id' => $request->booking_id ?? null,
            'pricing_plan_id' => $request->pricing_plan_id ?? null,
            'transaction_id' => $transactionId,
            'type' => $request->type,
            'method' => 'mpesa',
            'status' => 'pending',
            'amount' => $amount,
            'currency' => 'KES',
            'phone_number' => $request->phone_number,
            'metadata' => [
                'description' => $description,
            ],
        ]);

        // Simulate M-Pesa STK Push (in real implementation, integrate with actual M-Pesa API)
        $mpesaResponse = $this->simulateMpesaStkPush([
            'phone_number' => $request->phone_number,
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'description' => $description,
        ]);

        return response()->json([
            'message' => 'M-Pesa payment initiated. Please check your phone to complete the transaction.',
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'phone_number' => $request->phone_number,
                'status' => 'pending',
                'mpesa_response' => $mpesaResponse,
            ],
        ], 201);
    }

    public function mpesaCallback(Request $request): JsonResponse
    {
        // This endpoint would be called by M-Pesa after payment completion
        // In production, you'd verify the callback is authentic from M-Pesa
        
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'status' => 'required|in:completed,failed',
            'mpesa_receipt' => 'required_if:status,completed|string',
            'failure_reason' => 'required_if:status,failed|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid callback data',
            ], 400);
        }

        $payment = Payment::where('transaction_id', $request->transaction_id)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        $payment->update([
            'status' => $request->status,
            'mpesa_receipt' => $request->mpesa_receipt ?? null,
            'failure_reason' => $request->failure_reason ?? null,
            'processed_at' => now(),
        ]);

        // If payment is successful, process the related actions
        if ($request->status === 'completed') {
            $this->processSuccessfulPayment($payment);
        }

        return response()->json([
            'message' => 'Callback processed successfully',
        ]);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'type' => $payment->type,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => $payment->formatted_amount,
                'currency' => $payment->currency,
                'phone_number' => $payment->phone_number,
                'mpesa_receipt' => $payment->mpesa_receipt,
                'failure_reason' => $payment->failure_reason,
                'created_at' => $payment->created_at,
                'processed_at' => $payment->processed_at,
                'booking' => $payment->booking ? [
                    'id' => $payment->booking->id,
                    'car_name' => $payment->booking->car->full_name,
                ] : null,
                'pricing_plan' => $payment->pricingPlan ? [
                    'id' => $payment->pricingPlan->id,
                    'name' => $payment->pricingPlan->name,
                ] : null,
            ],
        ]);
    }

    private function simulateMpesaStkPush(array $data): array
    {
        // This is a simulation. In production, integrate with actual M-Pesa API
        return [
            'MerchantRequestID' => 'merchant-' . uniqid(),
            'CheckoutRequestID' => 'checkout-' . uniqid(),
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'CustomerMessage' => 'Success. Request accepted for processing',
        ];
    }

    private function processSuccessfulPayment(Payment $payment): void
    {
        if ($payment->type === 'booking_payment' && $payment->booking) {
            // Update booking status to pending confirmation
            $payment->booking->update([
                'status' => 'pending', // Waiting for host confirmation
            ]);
        } elseif ($payment->type === 'listing_fee' && $payment->pricingPlan) {
            // Create or update user subscription
            $user = $payment->user;
            
            // Cancel existing active subscription if any
            UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $payment->pricingPlan->id,
                'payment_id' => $payment->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $payment->pricingPlan->billing_cycle === 'one_time' ? null : now()->addYear(),
            ]);

            // Assign host role if user purchased a plan
            $user->assignRole('host');
        }
    }
}
