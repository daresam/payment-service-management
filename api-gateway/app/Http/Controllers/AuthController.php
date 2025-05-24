<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validated->fails()) {
            return response([
                'message' => 'Validation error',
                'errors' => $validated->errors(),
            ], 403);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        return response()->json([

            'status' => 'success',
            'message' => 'Login successfully',
            'data' => [
                'token' => $this->generateToken($user),
                'type' => 'Bearer',
                'expires_at' => now()->addWeek()->toDateTimeString(),
                'user' => $user,
            ],
        ]);
    }

    public function getLoggedInUser()
    {
        $user = Auth::user();

        return response()->json([

            'status' => 'success',
            'message' => 'Get user successfully',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    private function generateToken($user)
    {
        return $user->createToken(
            'api-token',
            ['*'],
            now()->addWeek()
        )->plainTextToken;
    }

    public function tokenRevoke(Request $request)
    {
        $request->user()->tokens()->delete();

        return response([
            'message' => 'Tokens revoked',
        ]);
    }

    public function tokenRefresh(Request $request)
    {
        $request->user()->tokens()->delete();

        return response([
            'token' => $this->generateToken($request->user()),
            'type' => 'Bearer',
            'expires_at' => now()->addWeek()->toDateTimeString(),
        ]);
    }
}
