<?php

namespace App\Http\Controllers;

use App\Models\User; // <-- Added for registration
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // <-- Added for registration
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        // 1. Validate the incoming data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Attempt to log the user in
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // 3. Authentication passed...
            $request->session()->regenerate();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful! Redirecting...'
            ]);
        }

        // 4. Authentication failed...
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid email or password.'
        ], 401);
    }

    /**
     * Handle a registration attempt.
     */
    public function register(Request $request)
    {
        // 1. Validate the incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20', // Field for phone number
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Create the new user
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number, // Field for phone number
                'password' => Hash::make($request->password),
            ]);

            // 3. Log the new user in immediately
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful! Redirecting...'
            ], 201); // 201 means "Created"

        } catch (\Exception $e) {
            // 4. Handle any errors (like database connection issues)
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed. Please try again.',
                // 'debug_error' => $e->getMessage() // Uncomment this line for debugging
            ], 500);
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        // 1. Log the user out
        Auth::logout();

        // 2. Invalidate their session
        $request->session()->invalidate();

        // 3. Regenerate the CSRF token
        $request->session()->regenerateToken();

        // 4. Send a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully. Redirecting...'
        ]);
    }
}