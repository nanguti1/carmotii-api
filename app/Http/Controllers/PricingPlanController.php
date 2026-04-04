<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PricingPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = PricingPlan::active()
            ->ordered()
            ->get();

        return response()->json([
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->formatted_price,
                    'billing_cycle' => $plan->billing_cycle,
                    'features' => $plan->features,
                    'limitations' => $plan->limitations,
                    'max_listings' => $plan->max_listings,
                    'max_bookings_per_month' => $plan->max_bookings_per_month,
                    'priority_listing' => $plan->priority_listing,
                    'featured_search' => $plan->featured_search,
                    'advanced_analytics' => $plan->advanced_analytics,
                    'dedicated_support' => $plan->dedicated_support,
                    'api_access' => $plan->api_access,
                    'custom_branding' => $plan->custom_branding,
                ];
            }),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $pricingPlan = PricingPlan::findOrFail($request->pricing_plan_id);

        // Check if user already has an active subscription
        if ($user->subscription && $user->subscription->isActive()) {
            return response()->json([
                'message' => 'You already have an active subscription',
            ], 422);
        }

        return response()->json([
            'message' => 'Please complete payment to activate your subscription',
            'subscription_details' => [
                'plan' => $pricingPlan->name,
                'price' => $pricingPlan->formatted_price,
                'features' => $pricingPlan->features,
                'billing_cycle' => $pricingPlan->billing_cycle,
            ],
            'next_step' => 'Use the payment endpoint to complete the subscription',
        ]);
    }

    public function currentSubscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription?->load('pricingPlan');

        if (!$subscription || !$subscription->isActive()) {
            return response()->json([
                'message' => 'No active subscription found',
                'subscription' => null,
            ]);
        }

        return response()->json([
            'subscription' => [
                'id' => $subscription->id,
                'plan' => [
                    'id' => $subscription->pricingPlan->id,
                    'name' => $subscription->pricingPlan->name,
                    'description' => $subscription->pricingPlan->description,
                    'features' => $subscription->pricingPlan->features,
                    'max_listings' => $subscription->pricingPlan->max_listings,
                    'max_bookings_per_month' => $subscription->pricingPlan->max_bookings_per_month,
                ],
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'auto_renew' => $subscription->auto_renew,
                'listings_used' => $subscription->listings_used,
                'bookings_this_month' => $subscription->bookings_this_month,
                'features_used' => $subscription->features_used,
                'can_list_more_cars' => $subscription->canListMoreCars(),
                'can_accept_more_bookings' => $subscription->canAcceptMoreBookings(),
            ],
        ]);
    }
}
