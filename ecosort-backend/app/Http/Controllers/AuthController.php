<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WasteBank;
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
            WasteBank::create([
                'manager_id' => $user->id,
                'name' => 'Bank Sampah ' . $user->name,
                'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                'latitude' => -6.18840000,
                'longitude' => 106.76480000,
                'is_active' => true,
            ]);
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

        if ($user->status === 'suspend') {
            $adminEmail = User::role('super_admin')->first()?->email ?? 'superadmin@ecosort.test';
            return response()->json([
                'status' => 'error',
                'message' => "Akun Anda telah ditangguhkan karena melanggar ketentuan. Silakan hubungi via email: {$adminEmail} untuk klarifikasi."
            ], 403);
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
            'businessId' => 'required_if:role,manager|nullable|string'
        ]);

        try {
            $auth = app('firebase.auth');
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $auth->getUser($uid);

            $email = $firebaseUser->email;
            $name = $firebaseUser->displayName ?? 'User';

            $user = User::where('email', $email)->first();

            if ($user && $user->status === 'suspend') {
                $adminEmail = User::role('super_admin')->first()?->email ?? 'superadmin@ecosort.test';
                return response()->json([
                    'status' => 'error',
                    'message' => "Akun Anda telah ditangguhkan karena melanggar ketentuan. Silakan hubungi via email: {$adminEmail} untuk klarifikasi."
                ], 403);
            }

            $role = $request->role ?? 'user';

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => null,
                    'business_id' => $role === 'manager' ? $request->businessId : null,
                ]);

                if ($role === 'manager') {
                    $user->assignRole('bank_sampah');
                    WasteBank::create([
                        'manager_id' => $user->id,
                        'name' => 'Bank Sampah ' . $user->name,
                        'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                        'latitude' => -6.18840000,
                        'longitude' => 106.76480000,
                        'is_active' => true,
                    ]);
                } else {
                    $user->assignRole('user');
                }
            } else {
                // If user exists, but they explicitly register as a manager and do not have the bank_sampah role yet, elevate them!
                if ($role === 'manager' && !$user->hasRole('bank_sampah')) {
                    $user->update([
                        'business_id' => $request->businessId
                    ]);
                    $user->syncRoles(['bank_sampah']); // Assign bank_sampah and remove user role
                    
                    if (!WasteBank::where('manager_id', $user->id)->exists()) {
                        WasteBank::create([
                            'manager_id' => $user->id,
                            'name' => 'Bank Sampah ' . $user->name,
                            'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                            'latitude' => -6.18840000,
                            'longitude' => 106.76480000,
                            'is_active' => true,
                        ]);
                    }
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
                'phone' => $user->phone,
                'points' => $user->points,
                'balance' => $user->balance,
                'scan_count' => $user->scan_count,
                'created_at' => $user->created_at,
                'role' => $role
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        $role = 'user';
        if ($user->hasRole('super_admin')) {
            $role = 'admin';
        } else if ($user->hasRole('bank_sampah')) {
            $role = 'manager';
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'points' => $user->points,
                'balance' => $user->balance,
                'scan_count' => $user->scan_count,
                'created_at' => $user->created_at,
                'role' => $role
            ]
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // If the user registered via social login and password is null, allow bypass or just let it update
        if ($user->password && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama salah.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah.'
        ]);
    }
}
