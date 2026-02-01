<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request)
    {
        // Validate email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if email exists
        if (!\App\Models\User::where('email', $request->email)->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This email is not registered.'], 404);
            }
            return back()->withErrors([
                'email' => 'This email is not registered.'
            ]);
        }

        // Send reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->expectsJson()) {
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['message' => 'We have emailed your password reset link!'], 200);
            }
            return response()->json(['message' => 'Something went wrong. Try again.'], 500);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'We have emailed your password reset link!')
            : back()->withErrors(['email' => 'Something went wrong. Try again.']);
    }
}
