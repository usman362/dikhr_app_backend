<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Send a 6-digit OTP to the user's email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['email']],
            [
                'token'      => Hash::make($otp),
                'created_at' => Carbon::now(),
            ],
        );

        // Send OTP via email
        Mail::raw(
            "Your Community Dhikr password reset code is: {$otp}\n\nThis code expires in 15 minutes.",
            function ($message) use ($data) {
                $message->to($data['email'])
                    ->subject('Password Reset - Community Dhikr');
            }
        );

        return response()->json([
            'message' => 'OTP sent to your email address.',
        ]);
    }

    /**
     * Verify OTP and reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email', 'exists:users,email'],
            'otp'      => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $record) {
            abort(422, 'No password reset request found for this email.');
        }

        // Check expiry (15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            abort(422, 'OTP has expired. Please request a new one.');
        }

        // Verify OTP
        if (! Hash::check($data['otp'], $record->token)) {
            abort(422, 'Invalid OTP code.');
        }

        // Reset password
        $user = User::query()->where('email', $data['email'])->firstOrFail();
        $user->update(['password' => Hash::make($data['password'])]);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        // Clean up reset token
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        // Issue a fresh token
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Password reset successfully.',
            'user'    => $user,
            'token'   => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
