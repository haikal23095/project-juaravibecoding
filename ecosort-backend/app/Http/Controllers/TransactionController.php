<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\PriceCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Determine if it is a multi-item or single-item request
        if ($request->has('items')) {
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'waste_bank_id' => 'required|exists:waste_banks,id',
                'items' => 'required|array|min:1',
                'items.*.waste_category_id' => 'required|exists:waste_categories,id',
                'items.*.weight_kg' => 'required|numeric|min:0.1',
                'items.*.scan_method' => 'required|in:manual,ai_scan'
            ]);
            $items = $validated['items'];
        } else {
            // Legacy / Single Item Support
            $validated = $request->validate([
                'user_id' => 'sometimes|exists:users,id',
                'waste_bank_id' => 'required|exists:waste_banks,id',
                'waste_category_id' => 'required|exists:waste_categories,id',
                'weight_kg' => 'required|numeric|min:0.1',
                'scan_method' => 'required|in:manual,ai_scan'
            ]);
            $items = [[
                'waste_category_id' => $validated['waste_category_id'],
                'weight_kg' => $validated['weight_kg'],
                'scan_method' => $validated['scan_method']
            ]];
        }

        $userId = $validated['user_id'] ?? $request->user()?->id ?? 1; // Fallback to user 1 for easy testing without token
        $wasteBankId = $validated['waste_bank_id'];

        try {
            return DB::transaction(function () use ($userId, $wasteBankId, $items) {
                $totalEarnings = 0;
                $detailsToInsert = [];

                foreach ($items as $item) {
                    $priceCatalog = PriceCatalog::where('waste_bank_id', $wasteBankId)
                                                ->where('waste_category_id', $item['waste_category_id'])
                                                ->first();

                    if (!$priceCatalog) {
                        throw new \Exception('Kategori sampah tidak didukung oleh bank sampah ini.');
                    }

                    $subtotal = $item['weight_kg'] * $priceCatalog->price_per_kg;
                    $totalEarnings += $subtotal;

                    $detailsToInsert[] = [
                        'waste_category_id' => $item['waste_category_id'],
                        'weight_kg' => $item['weight_kg'],
                        'subtotal' => $subtotal,
                        'scan_method' => $item['scan_method'],
                    ];
                }

                // Create Transaction parent
                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'waste_bank_id' => $wasteBankId,
                    'total_earnings' => $totalEarnings,
                    'status' => 'completed' // POS should auto-complete the transaction
                ]);

                // Save details
                foreach ($detailsToInsert as $detail) {
                    $transaction->details()->create($detail);
                }

                // Eager load details and waste categories for complete response
                $transaction->load('details.wasteCategory');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Setoran sampah berhasil dibuat.',
                    'data' => $transaction
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->hasRole('bank_sampah')) {
            // Manager / Waste Bank
            $wasteBank = \App\Models\WasteBank::where('manager_id', $user->id)->first();
            if (!$wasteBank) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }
            $transactions = Transaction::with(['user', 'details.wasteCategory'])
                ->where('waste_bank_id', $wasteBank->id)
                ->latest()
                ->get();
        } else {
            // Regular User
            $transactions = Transaction::with(['wasteBank', 'details.wasteCategory'])
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $transactions
        ]);
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

    public function managerDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $wasteBank = \App\Models\WasteBank::where('manager_id', $user->id)->first();
        if (!$wasteBank) {
            if ($user->hasRole('bank_sampah')) {
                $wasteBank = \App\Models\WasteBank::create([
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

        // 1. Total Nasabah (unik) yang pernah setor (status completed) di bank sampah ini
        $totalNasabah = Transaction::where('waste_bank_id', $wasteBank->id)
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');

        // 2. Total Sampah (Bulan Ini) dalam kg
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalWeightKg = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.waste_bank_id', $wasteBank->id)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startOfMonth, $endOfMonth])
            ->sum('transaction_details.weight_kg');

        // 3. Total Kas Disalurkan: Sum of amounts from approved withdrawals
        $totalKasDisalurkan = \App\Models\Withdrawal::where('waste_bank_id', $wasteBank->id)
            ->where('status', 'approved')
            ->sum('amount');

        // 4. Recent activities
        $recentTransactions = Transaction::with(['user', 'details.wasteCategory'])
            ->where('waste_bank_id', $wasteBank->id)
            ->latest()
            ->limit(5)
            ->get();

        // 5. Total weight grouped by waste category for this month
        $wasteCategoryStats = DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('waste_categories', 'transaction_details.waste_category_id', '=', 'waste_categories.id')
            ->where('transactions.waste_bank_id', $wasteBank->id)
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startOfMonth, $endOfMonth])
            ->select('waste_categories.name', DB::raw('SUM(transaction_details.weight_kg) as total_weight_kg'))
            ->groupBy('waste_categories.id', 'waste_categories.name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_nasabah' => $totalNasabah,
                'total_sampah_bulan_ini_kg' => (float)$totalWeightKg,
                'total_kas_disalurkan' => (float)$totalKasDisalurkan,
                'recent_transactions' => $recentTransactions,
                'waste_category_stats' => $wasteCategoryStats
            ]
        ]);
    }
}
