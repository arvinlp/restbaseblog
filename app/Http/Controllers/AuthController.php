<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="پروژه سیستم وبلاگ به صورت RestApi"
 * )
 */
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="ثبت‌نام کاربر جدید",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","first_name","last_name","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="first_name", type="string", example="Test"),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="ثبت‌نام موفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=409, description="ایمیل تکراری یا خطا")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'password' => 'required|string|confirmed'
        ]);
        // Check email is exists
        if (User::where('email', $request->email)->exists()) {
            return response()->json(['message' => 'This email is already registered.'], 409);
        }
        // Check email is verified
        $verification = EmailVerification::where('email', $request->email)->where('status', 1)->first();
        if (!$verification) {
            return response()->json(['message' => 'Email is not verified.'], 422);
        }
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $nickname = $request->nickname ?? $first_name . ' ' . $last_name;
        $user = new User;
        $user->nickname = $nickname;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->type = 'user';
        $user->save();

        EmailVerification::where('email', $request->email)->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="ورود کاربر",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ورود موفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="اطلاعات ورود اشتباه است")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/send-code",
     *     summary="ارسال کد تایید ایمیل",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="کد ارسال شد")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/verify-code",
     *     summary="تایید کد ایمیل",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="کد معتبر است"),
     *     @OA\Response(response=422, description="کد نامعتبر یا منقضی")
     * )
     */
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
        $record->status = 1;
        $record->save();
        return response()->json(['message' => 'Verification code is valid.']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="خروج کاربر (نیازمند احراز هویت)",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="خروج موفق",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="You have been logged out successfully."))
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت")
     * )
     */
    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'You have been logged out successfully.'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="درخواست کد بازیابی رمز عبور",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="کد بازیابی ارسال شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Your password reset code has been sent."))
     *     )
     * )
     */
    // Reset Password
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/verify-reset-code",
     *     summary="تایید کد بازیابی رمز عبور",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="کد معتبر است",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Reset code is valid."))
     *     ),
     *     @OA\Response(response=422, description="کد نامعتبر یا منقضی")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/change-password",
     *     summary="تغییر رمز عبور با کد بازیابی",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(response=200, description="رمز عبور با موفقیت تغییر کرد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Your password has been changed successfully."))
     *     ),
     *     @OA\Response(response=422, description="کد نامعتبر یا کاربر پیدا نشد")
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/v1/auth/user",
     *     summary="ویرایش اطلاعات کاربر احراز هویت شده",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="Ali"),
     *             @OA\Property(property="last_name", type="string", example="Rezaei"),
     *             @OA\Property(property="nickname", type="string", example="Ali R."),
     *             @OA\Property(property="email", type="string", example="ali@example.com"),
     *             @OA\Property(property="password", type="string", example="newpassword"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword")
     *         )
     *     ),
     *     @OA\Response(response=200, description="اطلاعات کاربر با موفقیت ویرایش شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your information has been updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     )
     * )
     */
    public function updateUser(Request $request)
    {
        $request->validate([
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'nickname' => 'sometimes|string',
            'email' => 'sometimes|required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $first_name = $request->input('first_name', $user->first_name);
        $last_name = $request->input('last_name', $user->last_name);
        $nickname = $request->input('nickname', $user->nickname) ?? $first_name . ' ' . $last_name;

        // Update only if the field is present in the request
        $user->nickname = $nickname;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        if ($request->has('password'))  $user->password = bcrypt($request->input('password'));
        $user->save();

        return response()->json(['message' => 'Your information has been updated successfully.', 'user' => $user]);
    }
}

/**
 * @OA\Schema(
 *   schema="UserResponse",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="first_name", type="string", example="Ali"),
 *   @OA\Property(property="last_name", type="string", example="Rezaei"),
 *   @OA\Property(property="nickname", type="string", example="Ali R."),
 *   @OA\Property(property="email", type="string", example="ali@example.com"),
 *   @OA\Property(property="type", type="string", example="user"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
