<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\WasteBank;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('bank_sampah')) {
            $wasteBank = WasteBank::where('manager_id', $user->id)->first();
            if (!$wasteBank) {
                return response()->json(['status' => 'success', 'data' => []]);
            }
            $withdrawals = Withdrawal::with('user')
                ->where('waste_bank_id', $wasteBank->id)
                ->latest()
                ->get();
        } else {
            $withdrawals = Withdrawal::with('wasteBank')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }
        return response()->json(['status' => 'success', 'data' => $withdrawals]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'waste_bank_id' => 'nullable|exists:waste_banks,id'
        ]);

        $user = $request->user();

        if ($user->balance < $validated['amount']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo Anda tidak mencukupi untuk melakukan penarikan ini.'
            ], 400);
        }

        $wasteBankId = $validated['waste_bank_id'];
        if (!$wasteBankId) {
            $defaultBank = WasteBank::where('is_active', true)->first();
            if (!$defaultBank) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada bank sampah aktif untuk memproses penarikan Anda saat ini.'
                ], 400);
            }
            $wasteBankId = $defaultBank->id;
        }

        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'waste_bank_id' => $wasteBankId,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan penarikan berhasil dibuat.',
            'data' => $withdrawal
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $withdrawal = Withdrawal::find($id);
        if (!$withdrawal) {
            return response()->json(['status' => 'error', 'message' => 'Permintaan penarikan tidak ditemukan.'], 404);
        }

        if ($withdrawal->status !== 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Permintaan penarikan ini sudah diproses.'], 400);
        }

        try {
            return DB::transaction(function () use ($withdrawal, $validated) {
                $withdrawal->update(['status' => $validated['status']]);

                if ($validated['status'] === 'approved') {
                    $user = $withdrawal->user;
                    if ($user->balance < $withdrawal->amount) {
                        throw new \Exception('Saldo nasabah tidak mencukupi untuk penarikan ini.');
                    }
                    $user->decrement('balance', $withdrawal->amount);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Status penarikan berhasil diperbarui.',
                    'data' => $withdrawal
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
