<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function sendVerificationCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $code = rand(100000, 999999);

        EmailVerification::updateOrCreate(
            ['email' => $request->email],
            ['code' => $code, 'created_at' => now()]
        );

        Mail::to($request->email)->send(new VerificationCodeMail($code));

        return response()->json(['message' => 'Your verification code has been sent.']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string'
        ]);

        $record = EmailVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 5) {
            return response()->json(['message' => 'The verification code has expired.'], 410);
        }

        return response()->json(['message' => 'Verification code is valid.']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:email_verifications,email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'This email is already registered.'], 409);
        }

        $user = User::create([
            'email' => $request->email,
            'name' => $request->first_name . ' ' . $request->last_name,
            'password' => bcrypt($request->password),
        ]);

        EmailVerification::where('email', $request->email)->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $user
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid login credentials.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'You have been logged out successfully.'
        ]);
    }


    public function resetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $code = rand(100000, 999999);

        EmailVerification::updateOrCreate(
            ['email' => $request->email],
            ['code' => $code, 'created_at' => now()]
        );

        Mail::to($request->email)->send(new VerificationCodeMail($code));

        return response()->json(['message' => 'Your password reset code has been sent.']);
    }
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $record = EmailVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid reset code.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 5) {
            return response()->json(['message' => 'The reset code has expired.'], 410);
        }

        return response()->json(['message' => 'Reset code is valid.']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);

        $record = EmailVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid reset code.'], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        EmailVerification::where('email', $request->email)->delete();

        return response()->json(['message' => 'Your password has been changed successfully.']);
    }

    /**
     * Get the authenticated user.
     */
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }
    public function updateUser(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email') && $user->email !== $request->email) {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json(['message' => 'Your information has been updated successfully.', 'user' => $user]);
    }
}
