<?php

namespace App\Http\Controllers;

use App\Models\WasteBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WasteBankController extends Controller
{
    public function index(): JsonResponse
    {
        $wasteBanks = WasteBank::with(['manager', 'priceCatalogs.wasteCategory'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $wasteBanks
        ]);
    }

    public function managerInventory(Request $request): JsonResponse
    {
        $user = $request->user();
        $wasteBank = WasteBank::where('manager_id', $user->id)->first();
        if (!$wasteBank) {
            if ($user->hasRole('bank_sampah')) {
                $wasteBank = WasteBank::create([
                    'manager_id' => $user->id,
                    'name' => 'Bank Sampah ' . $user->name,
                    'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                    'latitude' => -6.18840000,
                    'longitude' => 106.76480000,
                    'is_active' => true,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak terdaftar sebagai pengelola bank sampah aktif.'
                ], 403);
            }
        }

        $categories = \App\Models\WasteCategory::where('waste_bank_id', $wasteBank->id)->get();

        $stock = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.waste_bank_id', $wasteBank->id)
            ->where('transactions.status', 'completed')
            ->select('transaction_details.waste_category_id', DB::raw('SUM(transaction_details.weight_kg) as total_weight_kg'))
            ->groupBy('transaction_details.waste_category_id')
            ->get()
            ->keyBy('waste_category_id');

        $inventory = $categories->map(function ($cat) use ($stock) {
            $totalWeight = isset($stock[$cat->id]) ? (float)$stock[$cat->id]->total_weight_kg : 0.0;
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'code' => $cat->code,
                'unit' => $cat->unit ?? 'kg',
                'total_weight_kg' => $totalWeight
            ];
        });

        return response()->json([
            'status' => 'success',
            'waste_bank_name' => $wasteBank->name,
            'data' => $inventory
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $wasteBank = WasteBank::where('manager_id', $user->id)->first();
        
        if (!$wasteBank) {
            if ($user->hasRole('bank_sampah')) {
                $wasteBank = WasteBank::create([
                    'manager_id' => $user->id,
                    'name' => 'Bank Sampah ' . $user->name,
                    'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',
                    'latitude' => -6.18840000,
                    'longitude' => 106.76480000,
                    'is_active' => true,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak terdaftar sebagai pengelola bank sampah aktif.'
                ], 403);
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_active' => 'required|boolean'
        ]);

        $wasteBank->update([
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Informasi bank sampah berhasil diperbarui.',
            'data' => $wasteBank
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_active' => 'required|boolean',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        
        $wasteBank = WasteBank::create($validated);
        return response()->json([
            'status' => 'success',
            'message' => 'Waste bank created successfully',
            'data' => $wasteBank->load('manager')
        ]);
    }

    public function adminUpdate(Request $request, $id): JsonResponse
    {
        $wasteBank = WasteBank::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_active' => 'required|boolean',
            'manager_id' => 'nullable|exists:users,id',
        ]);
        
        $wasteBank->update($validated);
        return response()->json([
            'status' => 'success',
            'message' => 'Waste bank updated successfully',
            'data' => $wasteBank->load('manager')
        ]);
    }

    public function adminDestroy($id): JsonResponse
    {
        $wasteBank = WasteBank::findOrFail($id);
        $wasteBank->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Waste bank deleted successfully'
        ]);
    }
}
