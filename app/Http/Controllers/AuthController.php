<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Facades\Auth;

class AuthController extends Controller
{
    // Login Admin
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah, silahkan coba lagi'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'token' => $token,
            'user' => $user,
        ]);
    }

    // Logout Admin
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout Berhasil']);
    }

    // User Login Check
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
