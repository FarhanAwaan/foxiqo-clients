<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(): View
    {
        $settings = [
            // Company/Branding
            'company_name' => SystemSetting::getValue('company_name', 'Foxiqo Client Portal'),
            'company_email' => SystemSetting::getValue('company_email', ''),

            // Retell AI
            'retell_api_key' => SystemSetting::getValue('retell_api_key', ''),
            'retell_webhook_secret' => SystemSetting::getValue('retell_webhook_secret', ''),

            // Stripe
            'stripe_api_key' => SystemSetting::getValue('stripe_api_key', ''),
            'stripe_webhook_secret' => SystemSetting::getValue('stripe_webhook_secret', ''),

            // Payoneer
            'payoneer_api_key' => SystemSetting::getValue('payoneer_api_key', ''),
            'payoneer_partner_id' => SystemSetting::getValue('payoneer_partner_id', ''),

            // Billing
            'invoice_due_days' => SystemSetting::getValue('invoice_due_days', 7),
            'payment_link_expiry_days' => SystemSetting::getValue('payment_link_expiry_days', 14),
            'circuit_breaker_threshold' => SystemSetting::getValue('circuit_breaker_threshold', 150),
        ];

        // Check which sensitive fields have values (for display purposes)
        $hasValues = [
            'retell_api_key' => !empty($settings['retell_api_key']),
            'retell_webhook_secret' => !empty($settings['retell_webhook_secret']),
            'stripe_api_key' => !empty($settings['stripe_api_key']),
            'stripe_webhook_secret' => !empty($settings['stripe_webhook_secret']),
            'payoneer_api_key' => !empty($settings['payoneer_api_key']),
        ];

        return view('admin.settings.index', compact('settings', 'hasValues'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Company/Branding
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255'],

            // Retell AI (optional - only update if provided)
            'retell_api_key' => ['nullable', 'string'],
            'retell_webhook_secret' => ['nullable', 'string'],

            // Stripe (optional - only update if provided)
            'stripe_api_key' => ['nullable', 'string'],
            'stripe_webhook_secret' => ['nullable', 'string'],

            // Payoneer (optional - only update if provided)
            'payoneer_api_key' => ['nullable', 'string'],
            'payoneer_partner_id' => ['nullable', 'string', 'max:100'],

            // Billing
            'invoice_due_days' => ['required', 'integer', 'min:1', 'max:30'],
            'payment_link_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'circuit_breaker_threshold' => ['required', 'integer', 'min:100', 'max:300'],
        ]);

        // Save Company/Branding settings
        SystemSetting::setValue('company_name', $validated['company_name'], 'string');
        SystemSetting::setValue('company_email', $validated['company_email'], 'string');

        // Save Retell AI settings (only if provided)
        if ($request->filled('retell_api_key')) {
            SystemSetting::setValue('retell_api_key', $validated['retell_api_key'], 'encrypted', true);
        }
        if ($request->filled('retell_webhook_secret')) {
            SystemSetting::setValue('retell_webhook_secret', $validated['retell_webhook_secret'], 'encrypted', true);
        }

        // Save Stripe settings (only if provided)
        if ($request->filled('stripe_api_key')) {
            SystemSetting::setValue('stripe_api_key', $validated['stripe_api_key'], 'encrypted', true);
        }
        if ($request->filled('stripe_webhook_secret')) {
            SystemSetting::setValue('stripe_webhook_secret', $validated['stripe_webhook_secret'], 'encrypted', true);
        }

        // Save Payoneer settings (only if provided)
        if ($request->filled('payoneer_api_key')) {
            SystemSetting::setValue('payoneer_api_key', $validated['payoneer_api_key'], 'encrypted', true);
        }
        if ($request->filled('payoneer_partner_id')) {
            SystemSetting::setValue('payoneer_partner_id', $validated['payoneer_partner_id'], 'string');
        }

        // Save Billing settings
        SystemSetting::setValue('invoice_due_days', $validated['invoice_due_days'], 'integer');
        SystemSetting::setValue('payment_link_expiry_days', $validated['payment_link_expiry_days'], 'integer');
        SystemSetting::setValue('circuit_breaker_threshold', $validated['circuit_breaker_threshold'], 'integer');

        return back()->with('success', 'Settings updated successfully.');
    }
}
