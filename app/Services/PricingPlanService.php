<?php

namespace App\Services;

use App\Models\PricingPlan;
use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PricingPlanService
{
    /**
     * Get all active pricing plans
     */
    public function getActivePlans(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return PricingPlan::where('is_active', true)
                ->orderBy('price', 'asc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Subscribe to a pricing plan
     */
    public function subscribe(array $data, User $user): UserSubscription
    {
        try {
            $validator = Validator::make($data, [
                'pricing_plan_id' => 'required|exists:pricing_plans,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $pricingPlan = PricingPlan::findOrFail($data['pricing_plan_id']);

            // Check if plan is active
            if (!$pricingPlan->is_active) {
                throw ValidationException::withMessages([
                    'plan' => ['This pricing plan is not available.'],
                ]);
            }

            // Check if user already has active subscription
            $activeSubscription = UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeSubscription) {
                throw ValidationException::withMessages([
                    'subscription' => ['You already have an active subscription.'],
                ]);
            }

            // Check if user has host role
            if (!$user->hasRole('host')) {
                throw ValidationException::withMessages([
                    'user' => ['You must be a host to subscribe to a pricing plan.'],
                ]);
            }

            // Create subscription (pending payment)
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'pricing_plan_id' => $pricingPlan->id,
                'status' => 'pending', // Pending payment
                'start_date' => null,
                'end_date' => null,
                'auto_renew' => true,
            ]);

            return $subscription->load(['pricingPlan']);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get user's current subscription
     */
    public function getCurrentSubscription(User $user): ?UserSubscription
    {
        try {
            return UserSubscription::with(['pricingPlan'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get all user subscriptions
     */
    public function getUserSubscriptions(User $user): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return UserSubscription::with(['pricingPlan', 'payment'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Activate subscription after payment
     */
    public function activateSubscription(UserSubscription $subscription): UserSubscription
    {
        try {
            $pricingPlan = $subscription->pricingPlan;
            
            // Calculate start and end dates
            $startDate = now();
            $endDate = $this->calculateEndDate($startDate, $pricingPlan->billing_cycle);

            $subscription->update([
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(UserSubscription $subscription, User $user): UserSubscription
    {
        try {
            // Check if user owns the subscription or is admin
            if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'subscription' => ['You are not authorized to cancel this subscription.'],
                ]);
            }

            if ($subscription->status !== 'active') {
                throw ValidationException::withMessages([
                    'subscription' => ['Only active subscriptions can be cancelled.'],
                ]);
            }

            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check if user can create more cars based on their subscription
     */
    public function canCreateCar(User $user): bool
    {
        try {
            $subscription = $this->getCurrentSubscription($user);
            
            if (!$subscription) {
                return false; // No active subscription
            }

            $currentCarCount = $user->cars()->count();
            $maxCars = $subscription->pricingPlan->limitations['max_cars'] ?? 0;

            return $currentCarCount < $maxCars;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get subscription usage statistics
     */
    public function getSubscriptionUsage(User $user): array
    {
        try {
            $subscription = $this->getCurrentSubscription($user);
            
            if (!$subscription) {
                return [
                    'has_subscription' => false,
                    'message' => 'No active subscription found.',
                ];
            }

            $limitations = $subscription->pricingPlan->limitations ?? [];
            $usage = [];

            // Cars usage
            if (isset($limitations['max_cars'])) {
                $currentCars = $user->cars()->count();
                $usage['cars'] = [
                    'current' => $currentCars,
                    'max' => $limitations['max_cars'],
                    'remaining' => max(0, $limitations['max_cars'] - $currentCars),
                    'percentage' => ($currentCars / $limitations['max_cars']) * 100,
                ];
            }

            // Bookings usage
            if (isset($limitations['max_bookings_per_month'])) {
                $currentBookings = $user->bookings()
                    ->whereMonth('created_at', now()->month)
                    ->count();
                $usage['bookings'] = [
                    'current' => $currentBookings,
                    'max' => $limitations['max_bookings_per_month'],
                    'remaining' => max(0, $limitations['max_bookings_per_month'] - $currentBookings),
                    'percentage' => ($currentBookings / $limitations['max_bookings_per_month']) * 100,
                ];
            }

            return [
                'has_subscription' => true,
                'subscription' => $subscription,
                'usage' => $usage,
                'days_remaining' => $subscription->end_date ? 
                    Carbon::parse($subscription->end_date)->diffInDays(now()) : null,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Calculate end date based on billing cycle
     */
    private function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        switch ($billingCycle) {
            case 'monthly':
                return $startDate->addMonth();
            case 'quarterly':
                return $startDate->addMonths(3);
            case 'yearly':
                return $startDate->addYear();
            case 'one_time':
                return $startDate->addYear(); // Default to 1 year for one-time
            default:
                return $startDate->addMonth();
        }
    }

    /**
     * Check for expiring subscriptions (for notifications)
     */
    public function getExpiringSubscriptions(int $daysThreshold = 7): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return UserSubscription::with(['user', 'pricingPlan'])
                ->where('status', 'active')
                ->where('auto_renew', false)
                ->where('end_date', '<=', now()->addDays($daysThreshold))
                ->where('end_date', '>', now())
                ->orderBy('end_date', 'asc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Renew subscription
     */
    public function renewSubscription(UserSubscription $subscription): UserSubscription
    {
        try {
            if ($subscription->status !== 'active') {
                throw ValidationException::withMessages([
                    'subscription' => ['Only active subscriptions can be renewed.'],
                ]);
            }

            $pricingPlan = $subscription->pricingPlan;
            
            // Calculate new end date
            $currentEndDate = Carbon::parse($subscription->end_date);
            $newEndDate = $this->calculateEndDate($currentEndDate, $pricingPlan->billing_cycle);

            $subscription->update(['end_date' => $newEndDate]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
