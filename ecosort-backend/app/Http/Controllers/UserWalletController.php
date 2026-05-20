<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserWalletController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()->wallets
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100'
        ]);

        $user = $request->user();
        if ($user->wallets()->count() >= 3) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda hanya dapat menambahkan maksimal 3 rekening dompet.'
            ], 400);
        }

        $wallet = $user->wallets()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Rekening dompet berhasil ditambahkan.',
            'data' => $wallet
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:100'
        ]);

        $wallet = $request->user()->wallets()->find($id);
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening tidak ditemukan.'
            ], 404);
        }

        $wallet->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Rekening dompet berhasil diperbarui.',
            'data' => $wallet
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $wallet = $request->user()->wallets()->find($id);
        if (!$wallet) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rekening tidak ditemukan.'
            ], 404);
        }

        $wallet->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Rekening dompet berhasil dihapus.'
        ]);
    }
}
