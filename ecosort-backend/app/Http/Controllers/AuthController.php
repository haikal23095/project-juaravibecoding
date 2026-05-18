<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:user,manager'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone ?? null,
            'business_id' => $request->role === 'manager' ? $request->businessId : null,
        ]);

        if ($request->role === 'manager') {
            $user->assignRole('bank_sampah');
        } else {
            $user->assignRole('user');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully'
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Map spatie role to frontend role
        $role = 'user';
        if ($user->hasRole('super_admin')) {
            $role = 'admin';
        } else if ($user->hasRole('bank_sampah')) {
            $role = 'manager';
        }

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role
            ]
        ]);
    }

    public function googleAuth(Request $request)
    {
        $request->validate([
            'idToken' => 'required|string',
            'role' => 'nullable|in:user,manager',
            'businessId' => 'nullable|string'
        ]);

        try {
            $auth = app('firebase.auth');
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $auth->getUser($uid);

            $email = $firebaseUser->email;
            $name = $firebaseUser->displayName ?? 'User';

            $user = User::where('email', $email)->first();

            if (!$user) {
                $role = $request->role ?? 'user';
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => null,
                    'business_id' => $role === 'manager' ? $request->businessId : null,
                ]);

                if ($role === 'manager') {
                    $user->assignRole('bank_sampah');
                } else {
                    $user->assignRole('user');
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $frontendRole = 'user';
            if ($user->hasRole('super_admin')) {
                $frontendRole = 'admin';
            } else if ($user->hasRole('bank_sampah')) {
                $frontendRole = 'manager';
            }

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $frontendRole
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Firebase authentication failed: ' . $e->getMessage()
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        
        $role = 'user';
        if ($user->hasRole('super_admin')) {
            $role = 'admin';
        } else if ($user->hasRole('bank_sampah')) {
            $role = 'manager';
        }

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role
            ]
        ]);
    }
}
