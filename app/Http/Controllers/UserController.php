<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\SearchFilters\SearchFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
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
    public function create(Request $request)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
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

    public function update(Request $request, $id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        try {
            $data = User::findOrFail($id);
            $first_name = $request->input('first_name', $data->first_name);
            $last_name = $request->input('last_name', $data->last_name);
            $nickname = $request->input('nickname', $data->nickname) ?? $first_name . ' ' . $last_name;
            // Update only if the field is present in the request
            $data->nickname = $nickname;
            $data->first_name = $first_name;
            $data->last_name = $last_name;
            if ($request->has('password'))  $data->password = Hash::make($request->input('password'));
            if ($request->has('type'))      $data->type = $request->input('type', $data->type);
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

    public function delete($id = null)
    {
        if (self::isUserLoggedIn() === false) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        if(self::userData()->id == $id){
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
