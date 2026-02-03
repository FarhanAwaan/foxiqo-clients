<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $user->load('company');

        // Get activity logs for this user
        $activityLogs = AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return view('profile.index', compact('user', 'activityLogs'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $oldValues = $user->toArray();
        $user->update($validated);

        $this->auditService->log('profile_updated', $user, $oldValues);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();
        $oldValues = ['password' => '[REDACTED]'];

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->auditService->log('password_changed', $user, $oldValues);

        return back()->with('success', 'Password changed successfully.');
    }
}
