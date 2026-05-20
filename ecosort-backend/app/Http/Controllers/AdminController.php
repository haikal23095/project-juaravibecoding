<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WasteBank;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $totalUsers = User::count();
        $usersByRole = User::with('roles')->get()->groupBy(function($user) {
            return $user->roles->first()?->name ?? 'user';
        });
        
        $adminsCount = isset($usersByRole['super_admin']) ? $usersByRole['super_admin']->count() : 0;
        $managersCount = isset($usersByRole['bank_sampah']) ? $usersByRole['bank_sampah']->count() : 0;
        $regularUsersCount = isset($usersByRole['user']) ? $usersByRole['user']->count() : 0;
        
        $totalWasteBanks = WasteBank::count();
        $activeWasteBanks = WasteBank::where('is_active', true)->count();
        
        $totalTransactions = Transaction::count();
        $completedTransactions = Transaction::where('status', 'completed')->count();
        $totalPoints = Transaction::where('status', 'completed')->sum('total_earnings');
        $totalWeight = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->sum('transaction_details.weight_kg');
        
        $recentUsers = User::latest()->take(5)->get()->map(function($user) {
            $role = $user->roles->first()?->name ?? 'user';
            $user->role = $role === 'super_admin' ? 'admin' : ($role === 'bank_sampah' ? 'manager' : 'user');
            return $user;
        });
        
        $recentTransactions = Transaction::with(['user', 'wasteBank', 'details'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($tx) {
                $tx->weight = $tx->details->sum('weight_kg');
                return $tx;
            });
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_users' => $totalUsers,
                    'admins_count' => $adminsCount,
                    'managers_count' => $managersCount,
                    'regular_users_count' => $regularUsersCount,
                    'total_waste_banks' => $totalWasteBanks,
                    'active_waste_banks' => $activeWasteBanks,
                    'total_transactions' => $totalTransactions,
                    'completed_transactions' => $completedTransactions,
                    'total_points' => $totalPoints,
                    'total_weight' => $totalWeight,
                ],
                'recent_users' => $recentUsers,
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    public function listUsers(): JsonResponse
    {
        $users = User::with('roles')->get()->map(function($user) {
            $role = $user->roles->first()?->name ?? 'user';
            $user->role = $role === 'super_admin' ? 'admin' : ($role === 'bank_sampah' ? 'manager' : 'user');
            $user->phone_number = $user->phone;
            return $user;
        });
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:user,manager,admin',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
        ]);
        
        $roleName = $validated['role'] === 'manager' ? 'bank_sampah' : ($validated['role'] === 'admin' ? 'super_admin' : 'user');
        $user->assignRole($roleName);
        
        $user->role = $validated['role'];
        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ]);
    }

    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|in:user,manager,admin',
            'password' => 'nullable|string|min:8',
        ]);
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();
        
        $roleName = $validated['role'] === 'manager' ? 'bank_sampah' : ($validated['role'] === 'admin' ? 'super_admin' : 'user');
        $user->syncRoles([$roleName]);
        
        $user->role = $validated['role'];
        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroyUser($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}
