<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PricingPlan;

class PricingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'description' => 'Perfect for occasional car sharing',
                'price' => 2000.00,
                'billing_cycle' => 'one_time',
                'features' => [
                    'One-time listing fee',
                    'Basic listing features',
                    'Standard support',
                    'Basic insurance coverage',
                    'Up to 5 bookings per month',
                ],
                'limitations' => [
                    'No priority listing',
                    'Standard verification process',
                    'Basic analytics',
                ],
                'max_listings' => 1,
                'max_bookings_per_month' => 5,
                'priority_listing' => false,
                'featured_search' => false,
                'advanced_analytics' => false,
                'dedicated_support' => false,
                'api_access' => false,
                'custom_branding' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium',
                'description' => 'Best for active car owners',
                'price' => 5000.00,
                'billing_cycle' => 'one_time',
                'features' => [
                    'One-time listing fee',
                    'Priority listing placement',
                    'Premium support',
                    'Enhanced insurance coverage',
                    'Unlimited bookings',
                    'Advanced analytics',
                    'Fast-track verification',
                    'Featured in search results',
                ],
                'limitations' => [],
                'max_listings' => 5,
                'max_bookings_per_month' => null,
                'priority_listing' => true,
                'featured_search' => true,
                'advanced_analytics' => true,
                'dedicated_support' => true,
                'api_access' => false,
                'custom_branding' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For fleet owners and businesses',
                'price' => 10000.00,
                'billing_cycle' => 'one_time',
                'features' => [
                    'One-time listing fee',
                    'Multiple car listings',
                    'Dedicated account manager',
                    'Premium insurance coverage',
                    'Unlimited bookings',
                    'Advanced analytics & reporting',
                    'Instant verification',
                    'Priority support 24/7',
                    'Custom branding options',
                    'API access',
                ],
                'limitations' => [],
                'max_listings' => 50,
                'max_bookings_per_month' => null,
                'priority_listing' => true,
                'featured_search' => true,
                'advanced_analytics' => true,
                'dedicated_support' => true,
                'api_access' => true,
                'custom_branding' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            PricingPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
