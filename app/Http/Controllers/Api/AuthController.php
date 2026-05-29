<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Helpers\JwtHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;

class AuthController extends Controller
{
    /**
     * Register a new user and send verification email.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $verificationToken = Str::random(60);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'verification_token' => $verificationToken,
        ]);

        // Send Mailtrap verification link
        $verificationUrl = url('/api/verify-email?token=' . $verificationToken);
        Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationUrl));

        return response()->json([
            'message' => 'User registered successfully. Please check your email to verify your account.',
            'user' => $user
        ], 201);
    }

    /**
     * Authenticate user credentials, checking that email verification has completed.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'message' => 'Invalid email or password'
                ], 401);
            }

            // Guard: Prevent login of unverified email address profiles
            if (is_null($user->email_verified_at)) {
                return response()->json([
                    'message' => 'Your email address is not verified. Please check your inbox or resend the verification link.',
                    'email_unverified' => true,
                    'email' => $user->email
                ], 403);
            }

            $token = JwtHelper::generateToken($user->id, 'admin', 60); // 60 minutes (1 hour) expiration

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'admin',
                ],
                'token' => $token,
                'expires_in' => 3600
            ]);
        }

        // Check if employee
        $employee = \App\Models\Employee::where('email', $validated['email'])->first();

        if ($employee) {
            if (!$employee->password || !Hash::check($validated['password'], $employee->password)) {
                return response()->json([
                    'message' => 'Invalid email or password'
                ], 401);
            }

            if ($employee->status !== 'Active') {
                return response()->json([
                    'message' => 'Your account is inactive. Please contact the administrator.'
                ], 403);
            }

            $token = JwtHelper::generateToken($employee->id, 'employee', 60);

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => 'employee',
                ],
                'token' => $token,
                'expires_in' => 3600
            ]);
        }

        return response()->json([
            'message' => 'Invalid email or password'
        ], 401);
    }

    /**
     * Get the authenticated user details.
     */
    public function me(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $role = $user instanceof \App\Models\Employee ? 'employee' : 'admin';
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'user_details' => $user
        ]);
    }

    /**
     * Refresh the authenticated user session's JWT token.
     */
    public function refresh(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $role = $user instanceof \App\Models\Employee ? 'employee' : 'admin';
        $token = JwtHelper::generateToken($user->id, $role, 60);

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token,
            'expires_in' => 3600
        ]);
    }

    /**
     * Verify user email via token link and redirect to frontend with query parameters.
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/?verification_error=missing_token');
        }

        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/?verification_error=invalid_token');
        }

        $user->email_verified_at = now();
        $user->verification_token = null;
        $user->save();

        return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/?verified=true');
    }

    /**
     * Resend verification email to user inbox.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'This email address is already verified.'
            ], 400);
        }

        if (!$user->verification_token) {
            $user->verification_token = Str::random(60);
            $user->save();
        }

        $verificationUrl = url('/api/verify-email?token=' . $user->verification_token);
        Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationUrl));

        return response()->json([
            'message' => 'Verification email resent successfully! Please check your inbox.'
        ]);
    }
}
