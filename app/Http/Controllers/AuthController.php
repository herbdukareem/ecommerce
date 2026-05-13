<?php

namespace App\Http\Controllers;

use App\Mail\CustomerRegistrationVerificationCode;
use App\Models\PendingCustomerRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Role;
use App\Services\ReferralService;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'referral_code' => 'nullable|string|max:64',
        ]);

        $code = (string) random_int(100000, 999999);
        $referralCode = app(ReferralService::class)->findActiveCode($data['referral_code'] ?? null);

        PendingCustomerRegistration::query()->updateOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'verification_code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
                'verified_at' => null,
                'ip_address' => $request->ip(),
                'referral_code' => $referralCode?->code ?: ($data['referral_code'] ?? null),
                'referral_code_id' => $referralCode?->id,
                'referral_ip_address' => $request->ip(),
            ]
        );

        Mail::to($data['email'])->send(new CustomerRegistrationVerificationCode($data['name'], $code));

        return response()->json([
            'message' => 'A verification code has been sent to your email.',
            'verification_required' => true,
            'email' => strtolower($data['email']),
        ], 202);
    }

    public function verifyRegistration(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'code' => 'required|string|size:6',
        ]);

        $pending = PendingCustomerRegistration::query()
            ->where('email', strtolower($data['email']))
            ->first();

        if (!$pending) {
            throw ValidationException::withMessages([
                'email' => ['No pending registration was found for this email.'],
            ]);
        }

        if ($pending->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['This verification code has expired. Please request a new code.'],
            ]);
        }

        if ($pending->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Please request a new code.'],
            ]);
        }

        if (!Hash::check($data['code'], $pending->verification_code_hash)) {
            $pending->increment('attempts');
            throw ValidationException::withMessages([
                'code' => ['The verification code is incorrect.'],
            ]);
        }

        $user = User::create([
            'name' => $pending->name,
            'email' => $pending->email,
            'password' => $pending->password,
            'email_verified_at' => now(),
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole(Role::findOrCreate('Customer', 'sanctum'));
        }

        app(ReferralService::class)->linkVerifiedUser($user, $pending->referral_code);

        $pending->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified and account created successfully.',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function resendRegistrationCode(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        $pending = PendingCustomerRegistration::query()
            ->where('email', strtolower($data['email']))
            ->first();

        if (!$pending) {
            throw ValidationException::withMessages([
                'email' => ['No pending registration was found for this email.'],
            ]);
        }

        if ($pending->updated_at && $pending->updated_at->gt(now()->subMinute())) {
            throw ValidationException::withMessages([
                'email' => ['Please wait a moment before requesting another code.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $pending->update([
            'verification_code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($pending->email)->send(new CustomerRegistrationVerificationCode($pending->name, $code));

        return response()->json([
            'message' => 'A new verification code has been sent.',
        ]);
    }

    /**
     * Login a user via Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->hasRole('Customer') && !$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before signing in.'],
            ]);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Return authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete all tokens to force re-login
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password changed successfully. Please login again.',
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Logout the user by deleting tokens.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    protected function userPayload(User $user): User
    {
        $user->load('roles');
        $user->setAttribute('permissions', $user->getAllPermissions()->pluck('name')->values());

        return $user;
    }
}
