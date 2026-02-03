<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate([
            'email' => 'farhan@foxiqo.com',
        ], [
            'uuid' => Str::uuid(),
            'company_id' => null,
            'first_name' => 'Farhan',
            'last_name' => 'Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create sample company
        $company = Company::firstOrCreate([
            'email' => 'demo@acmecorp.com',
        ], [
            'uuid' => Str::uuid(),
            'name' => 'Acme Corporation',
            'billing_email' => 'billing@acmecorp.com',
            'phone' => '+1 (555) 123-4567',
            'address' => '123 Business Ave',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94102',
            'country' => 'United States',
            'status' => 'active',
        ]);

        // Create sample customer user (active)
        User::firstOrCreate([
            'email' => 'john@acmecorp.com',
        ], [
            'uuid' => Str::uuid(),
            'company_id' => $company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create default plans
        $plans = [
            [
                'name' => 'Starter',
                'description' => 'Perfect for small businesses with moderate call volume',
                'price' => 149.00,
                'included_minutes' => 500,
            ],
            [
                'name' => 'Growth',
                'description' => 'For growing businesses with higher call volume',
                'price' => 300.00,
                'included_minutes' => 1200,
            ],
            [
                'name' => 'Professional',
                'description' => 'For established businesses with significant call volume',
                'price' => 500.00,
                'included_minutes' => 2500,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['name' => $plan['name']], $plan);
        }

        // Create default system settings
        $settings = [
            ['key' => 'retell_api_key', 'value' => '', 'type' => 'encrypted', 'is_sensitive' => true, 'description' => 'Retell AI API Key'],
            ['key' => 'retell_webhook_secret', 'value' => '', 'type' => 'encrypted', 'is_sensitive' => true, 'description' => 'Retell Webhook Secret'],
            ['key' => 'payoneer_api_key', 'value' => '', 'type' => 'encrypted', 'is_sensitive' => true, 'description' => 'Payoneer API Key'],
            ['key' => 'payoneer_partner_id', 'value' => '', 'type' => 'string', 'is_sensitive' => false, 'description' => 'Payoneer Partner ID'],
            ['key' => 'stripe_api_key', 'value' => '', 'type' => 'encrypted', 'is_sensitive' => true, 'description' => 'Stripe API Key'],
            ['key' => 'stripe_webhook_secret', 'value' => '', 'type' => 'encrypted', 'is_sensitive' => true, 'description' => 'Stripe Webhook Secret'],
            ['key' => 'company_name', 'value' => 'Foxiqo Client Portal', 'type' => 'string', 'is_sensitive' => false, 'description' => 'Company Name for Branding'],
            ['key' => 'company_email', 'value' => 'farhan@foxiqo.com', 'type' => 'string', 'is_sensitive' => false, 'description' => 'Company Support Email'],
            ['key' => 'circuit_breaker_threshold', 'value' => '150', 'type' => 'integer', 'is_sensitive' => false, 'description' => 'Circuit breaker threshold percentage'],
            ['key' => 'invoice_due_days', 'value' => '7', 'type' => 'integer', 'is_sensitive' => false, 'description' => 'Days until invoice is due'],
            ['key' => 'payment_link_expiry_days', 'value' => '14', 'type' => 'integer', 'is_sensitive' => false, 'description' => 'Days until payment link expires'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
