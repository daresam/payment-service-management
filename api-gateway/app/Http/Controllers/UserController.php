<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validated->fails()) {
            return response([
                'status' => 'error',
                'message' => 'Validation error',
                'error' => $validated->errors(),
            ], 403);
        }

        $validatedData = $validated->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
        ]);

        $token = $user->createToken(
            'api-token',
            ['*'],
            now()->addWeek()
        )->plainTextToken;

        $response = [
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ];

        return response($response, 201);
    }
}
