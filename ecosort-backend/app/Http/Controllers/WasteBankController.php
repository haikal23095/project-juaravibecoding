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
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pengelola bank sampah aktif.'
            ], 403);
        }

        $categories = \App\Models\WasteCategory::all();

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
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak terdaftar sebagai pengelola bank sampah aktif.'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        $wasteBank->update([
            'name' => $request->name,
            'address' => $request->address,
            'is_active' => $request->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Informasi bank sampah berhasil diperbarui.',
            'data' => $wasteBank
        ]);
    }
}
