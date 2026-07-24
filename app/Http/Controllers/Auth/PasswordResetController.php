<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function showForm(string $token): View|RedirectResponse
    {
        $user = User::where('password_reset_token', $token)
            ->where('password_reset_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired password reset link. Please contact your administrator.');
        }

        return view('auth.reset-password', compact('user', 'token'));
    }

    public function complete(Request $request, string $token): RedirectResponse
    {
        $user = User::where('password_reset_token', $token)
            ->where('password_reset_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired password reset link. Please contact your administrator.');
        }

        $request->validate([
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $oldValues = ['password' => '[REDACTED]'];

        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null,
        ]);

        $this->auditService->log('password_reset_completed', $user, $oldValues);

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. Please log in with your new password.');
    }
}
