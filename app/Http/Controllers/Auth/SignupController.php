<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SignupController extends Controller
{
    public function showForm(string $token): View|RedirectResponse
    {
        $user = User::where('signup_token', $token)
            ->where('signup_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired signup link. Please contact your administrator.');
        }

        return view('auth.signup', compact('user', 'token'));
    }

    public function complete(Request $request, string $token): RedirectResponse
    {
        $user = User::where('signup_token', $token)
            ->where('signup_token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired signup link. Please contact your administrator.');
        }

        $request->validate([
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
            'email_verified_at' => now(),
            'signup_token' => null,
            'signup_token_expires_at' => null,
        ]);

        Auth::login($user);

        return redirect()->route('customer.dashboard')
            ->with('success', 'Welcome! Your account has been activated.');
    }
}
