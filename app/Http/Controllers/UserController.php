<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="لیست کاربران (نیازمند احراز هویت)",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست کاربران"
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $result = SearchFilter::apply($request, new User);
        return response()->json($result, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     summary="نمایش اطلاعات یک کاربر (نیازمند احراز هویت)",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="کاربر پیدا شد"
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="کاربر پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *     )
     * )
     */
    public function show(Request $request, $id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $result = User::findOrFail($id);
        return response()->json($result, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/new",
     *     summary="ایجاد کاربر جدید (نیازمند احراز هویت)",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="Ali"),
     *             @OA\Property(property="last_name", type="string", example="Rezaei"),
     *             @OA\Property(property="nickname", type="string", example="Ali R."),
     *             @OA\Property(property="email", type="string", example="ali@example.com"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="password_confirmation", type="string", example="secret123"),
     *             @OA\Property(property="type", type="string", example="user")
     *         )
     *     ),
     *     @OA\Response(response=201, description="کاربر با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User created successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="The given data was invalid."))
     *     )
     * )
     */
    public function create(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'nickname' => 'string',
                'password' => 'required|string|confirmed'
            ]);
            $first_name = $request->input('first_name', null);
            $last_name = $request->input('last_name', null);
            $nickname = $request->input('nickname', null) ?? $first_name . ' ' . $last_name;
            $data = new User;
            $data->nickname = $nickname;
            $data->first_name = $first_name;
            $data->last_name = $last_name;
            if ($request->has('email'))     $data->email = $request->input('email');
            if ($request->has('password'))  $data->password = Hash::make($request->input('password'));
            if ($request->has('type'))      $data->type = $request->input('type', 'user');
            $data->save();

            return response()->json([
                'message' => 'User created successfully',
                'data' => $data
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating User: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     summary="ویرایش کاربر (نیازمند احراز هویت)",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="Ali"),
     *             @OA\Property(property="last_name", type="string", example="Rezaei"),
     *             @OA\Property(property="nickname", type="string", example="Ali R."),
     *             @OA\Property(property="password", type="string", example="newpass123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpass123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="کاربر با موفقیت ویرایش شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=404, description="کاربر پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *     ),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="The given data was invalid."))
     *     )
     * )
     */
    public function update(Request $request, $id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $request->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'nickname' => 'string',
                'password' => 'required|string|confirmed'
            ]);
            $data = User::findOrFail($id);
            $first_name = $request->input('first_name', $data->first_name);
            $last_name = $request->input('last_name', $data->last_name);
            $nickname = $request->input('nickname', $data->nickname) ?? $first_name . ' ' . $last_name;
            // Update only if the field is present in the request
            $data->nickname = $nickname;
            $data->first_name = $first_name;
            $data->last_name = $last_name;
            if ($request->has('password'))  $data->password = Hash::make($request->input('password'));
            $data->save();

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating User: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     summary="حذف کاربر (نیازمند احراز هویت)",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="کاربر با موفقیت حذف شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User deleted successfully"))
     *     ),
     *     @OA\Response(response=401, description="نیازمند احراز هویت",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthorized"))
     *     ),
     *     @OA\Response(response=403, description="حذف حساب خود مجاز نیست",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="You can't delete your own account"))
     *     ),
     *     @OA\Response(response=404, description="کاربر پیدا نشد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *     )
     * )
     */
    public function delete($id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        if (self::userData()->id == $id) {
            return response()->json([
                'message' => 'You can`t delete your own account'
            ], 403);
        }
        try {
            if (User::destroy($id)) {
                return response()->json([
                    'message' => 'User deleted successfully'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting User: ' . $e->getMessage()
            ], 500);
        }
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
