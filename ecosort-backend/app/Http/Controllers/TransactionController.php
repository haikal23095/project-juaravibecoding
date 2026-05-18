<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\PriceCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'waste_bank_id' => 'required|exists:waste_banks,id',
            'waste_category_id' => 'required|exists:waste_categories,id',
            'weight_kg' => 'required|numeric|min:0.1',
            'scan_method' => 'required|in:manual,ai_scan'
        ]);

        $priceCatalog = PriceCatalog::where('waste_bank_id', $validated['waste_bank_id'])
                                    ->where('waste_category_id', $validated['waste_category_id'])
                                    ->first();

        if (!$priceCatalog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori sampah tidak didukung oleh bank sampah ini.'
            ], 400);
        }

        $totalEarnings = $validated['weight_kg'] * $priceCatalog->price_per_kg;

        $transaction = Transaction::create([
            'user_id' => $validated['user_id'] ?? $request->user()?->id ?? 1, // Fallback to user 1 for easy testing without token
            'waste_bank_id' => $validated['waste_bank_id'],
            'waste_category_id' => $validated['waste_category_id'],
            'weight_kg' => $validated['weight_kg'],
            'total_earnings' => $totalEarnings,
            'scan_method' => $validated['scan_method'],
            'status' => 'completed' // POS should auto-complete the transaction
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Setoran sampah berhasil dibuat, menunggu konfirmasi.',
            'data' => $transaction
        ], 201);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:completed,rejected'
        ]);

        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi tidak ditemukan.'
            ], 404);
        }

        $transaction->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status setoran sampah berhasil diperbarui.',
            'data' => $transaction
        ]);
    }
}
