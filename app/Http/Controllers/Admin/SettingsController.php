<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    /**
     * Encrypted settings this screen keeps masked and only reveals on request
     * (see reveal()); doubles as the whitelist for that endpoint.
     */
    public const SECRET_KEYS = [
        'retell_api_key', 'retell_webhook_secret',
        'stripe_api_key', 'stripe_webhook_secret',
        'paddle_api_key', 'paddle_webhook_secret', 'paddle_client_side_token',
        'google_calendar_client_secret',
    ];

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

            // Payment rail toggles — flip off at any time if a rail is having issues
            'nsave_enabled' => SystemSetting::getValue('nsave_enabled', true),
            'paddle_enabled' => SystemSetting::getValue('paddle_enabled', false),

            // Paddle
            'paddle_environment' => SystemSetting::getValue('paddle_environment', 'sandbox'),
            'paddle_api_key' => SystemSetting::getValue('paddle_api_key', ''),
            'paddle_webhook_secret' => SystemSetting::getValue('paddle_webhook_secret', ''),
            'paddle_client_side_token' => SystemSetting::getValue('paddle_client_side_token', ''),
            'paddle_product_id' => SystemSetting::getValue('paddle_product_id', ''),

            // Google Calendar (agency-wide OAuth app, used for all agent connections)
            'google_calendar_client_id' => SystemSetting::getValue('google_calendar_client_id', ''),
            'google_calendar_client_secret' => SystemSetting::getValue('google_calendar_client_secret', ''),

            // Billing
            'invoice_due_days' => SystemSetting::getValue('invoice_due_days', 7),
            'payment_link_expiry_days' => SystemSetting::getValue('payment_link_expiry_days', 14),
            'usage_alert_enabled' => SystemSetting::getValue('usage_alert_enabled', false),
            'usage_alert_minutes_threshold' => SystemSetting::getValue('usage_alert_minutes_threshold', 500),
        ];

        // Check which sensitive fields have values (for display purposes)
        $hasValues = collect(self::SECRET_KEYS)
            ->mapWithKeys(fn ($key) => [$key => !empty($settings[$key])])
            ->all();

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

            // Payment rail toggles
            'nsave_enabled' => ['nullable', 'boolean'],
            'paddle_enabled' => ['nullable', 'boolean'],

            // Paddle (optional - only update if provided)
            'paddle_environment' => ['nullable', 'string', 'in:sandbox,live'],
            'paddle_api_key' => ['nullable', 'string'],
            'paddle_webhook_secret' => ['nullable', 'string'],
            'paddle_client_side_token' => ['nullable', 'string'],
            'paddle_product_id' => ['nullable', 'string', 'max:100'],

            // Google Calendar (optional - only update if provided)
            'google_calendar_client_id' => ['nullable', 'string'],
            'google_calendar_client_secret' => ['nullable', 'string'],

            // Billing
            'invoice_due_days' => ['required', 'integer', 'min:1', 'max:30'],
            'payment_link_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'usage_alert_enabled' => ['nullable', 'boolean'],
            'usage_alert_minutes_threshold' => ['required', 'integer', 'min:1'],
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

        // Save payment rail toggles (checkboxes: absent means off)
        SystemSetting::setValue('nsave_enabled', $request->boolean('nsave_enabled'), 'boolean');
        SystemSetting::setValue('paddle_enabled', $request->boolean('paddle_enabled'), 'boolean');

        // Save Paddle settings (only if provided)
        if ($request->filled('paddle_environment')) {
            SystemSetting::setValue('paddle_environment', $validated['paddle_environment'], 'string');
        }
        if ($request->filled('paddle_api_key')) {
            SystemSetting::setValue('paddle_api_key', $validated['paddle_api_key'], 'encrypted', true);
        }
        if ($request->filled('paddle_webhook_secret')) {
            SystemSetting::setValue('paddle_webhook_secret', $validated['paddle_webhook_secret'], 'encrypted', true);
        }
        if ($request->filled('paddle_client_side_token')) {
            SystemSetting::setValue('paddle_client_side_token', $validated['paddle_client_side_token'], 'encrypted', true);
        }
        if ($request->filled('paddle_product_id')) {
            SystemSetting::setValue('paddle_product_id', $validated['paddle_product_id'], 'encrypted', true);
        }

        // Save Google Calendar settings (only if provided)
        if ($request->filled('google_calendar_client_id')) {
            SystemSetting::setValue('google_calendar_client_id', $validated['google_calendar_client_id'], 'string');
        }
        if ($request->filled('google_calendar_client_secret')) {
            SystemSetting::setValue('google_calendar_client_secret', $validated['google_calendar_client_secret'], 'encrypted', true);
        }

        // Save Billing settings
        SystemSetting::setValue('invoice_due_days', $validated['invoice_due_days'], 'integer');
        SystemSetting::setValue('payment_link_expiry_days', $validated['payment_link_expiry_days'], 'integer');
        SystemSetting::setValue('usage_alert_enabled', $request->boolean('usage_alert_enabled'), 'boolean');
        SystemSetting::setValue('usage_alert_minutes_threshold', $validated['usage_alert_minutes_threshold'], 'integer');

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Returns one stored secret in plain text, only when its eye button is clicked, so
     * secrets never sit in the page source. Every reveal is audit-logged (key name only,
     * never the value).
     */
    public function reveal(string $key): JsonResponse
    {
        abort_unless(in_array($key, self::SECRET_KEYS, true), 404);

        $this->auditService->logAction('setting_revealed', null, ['key' => $key]);

        // Reads the row directly instead of SystemSetting::getValue(): that caches the
        // decrypted value for an hour, so clicking the eye would leave a plain-text copy in
        // the cache table and could show a value that no longer matches what's stored.
        $setting = SystemSetting::where('key', $key)->first();
        $value = $setting?->value;
        if ($setting?->is_sensitive && $value) {
            $value = decrypt($value);
        }

        return response()
            ->json(['value' => (string) $value])
            ->header('Cache-Control', 'no-store, private');
    }
}
