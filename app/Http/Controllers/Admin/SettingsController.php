<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'retell_api_key' => SystemSetting::getValue('retell_api_key', ''),
            'payoneer_api_key' => SystemSetting::getValue('payoneer_api_key', ''),
            'payoneer_partner_id' => SystemSetting::getValue('payoneer_partner_id', ''),
            'invoice_due_days' => SystemSetting::getValue('invoice_due_days', 7),
            'payment_link_expiry_days' => SystemSetting::getValue('payment_link_expiry_days', 14),
            'circuit_breaker_threshold' => SystemSetting::getValue('circuit_breaker_threshold', 150),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retell_api_key' => ['nullable', 'string'],
            'payoneer_api_key' => ['nullable', 'string'],
            'payoneer_partner_id' => ['nullable', 'string'],
            'invoice_due_days' => ['required', 'integer', 'min:1', 'max:30'],
            'payment_link_expiry_days' => ['required', 'integer', 'min:1', 'max:60'],
            'circuit_breaker_threshold' => ['required', 'integer', 'min:100', 'max:300'],
        ]);

        // Save sensitive settings
        if ($request->filled('retell_api_key')) {
            SystemSetting::setValue('retell_api_key', $validated['retell_api_key'], 'string', true);
        }

        if ($request->filled('payoneer_api_key')) {
            SystemSetting::setValue('payoneer_api_key', $validated['payoneer_api_key'], 'string', true);
        }

        if ($request->filled('payoneer_partner_id')) {
            SystemSetting::setValue('payoneer_partner_id', $validated['payoneer_partner_id'], 'string', false);
        }

        // Save non-sensitive settings
        SystemSetting::setValue('invoice_due_days', $validated['invoice_due_days'], 'integer');
        SystemSetting::setValue('payment_link_expiry_days', $validated['payment_link_expiry_days'], 'integer');
        SystemSetting::setValue('circuit_breaker_threshold', $validated['circuit_breaker_threshold'], 'integer');

        return back()->with('success', 'Settings updated successfully.');
    }
}
