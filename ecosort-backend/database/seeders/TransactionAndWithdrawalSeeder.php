<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\WasteBank;
use App\Models\WasteCategory;
use App\Models\PriceCatalog;

class TransactionAndWithdrawalSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('user')->get();
        $banks = WasteBank::all();
        
        if ($users->isEmpty() || $banks->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $bank = $banks->random();
            $category = WasteCategory::inRandomOrder()->first();
            $priceCatalog = PriceCatalog::where('waste_bank_id', $bank->id)
                                        ->where('waste_category_id', $category->id)
                                        ->first();
            
            if (!$priceCatalog) {
                continue;
            }
            
            $weightCompleted = rand(10, 50) / 10;
            Transaction::create([
                'user_id' => $user->id,
                'waste_bank_id' => $bank->id,
                'waste_category_id' => $category->id,
                'weight_kg' => $weightCompleted,
                'total_earnings' => $weightCompleted * $priceCatalog->price_per_kg,
                'scan_method' => 'ai_scan',
                'status' => 'completed',
            ]);

            $category2 = WasteCategory::inRandomOrder()->first();
            $priceCatalog2 = PriceCatalog::where('waste_bank_id', $bank->id)
                                        ->where('waste_category_id', $category2->id)
                                        ->first();
                                        
            if (!$priceCatalog2) {
                continue;
            }
                                        
            $weightPending = rand(5, 30) / 10;
            Transaction::create([
                'user_id' => $user->id,
                'waste_bank_id' => $bank->id,
                'waste_category_id' => $category2->id,
                'weight_kg' => $weightPending,
                'total_earnings' => $weightPending * $priceCatalog2->price_per_kg,
                'scan_method' => 'manual',
                'status' => 'pending',
            ]);
        }

        if ($users->count() >= 2) {
            Withdrawal::create([
                'user_id' => $users[0]->id,
                'waste_bank_id' => $banks->random()->id,
                'amount' => 15000.00,
                'status' => 'approved',
            ]);

            Withdrawal::create([
                'user_id' => $users[1]->id,
                'waste_bank_id' => $banks->random()->id,
                'amount' => 10000.00,
                'status' => 'pending',
            ]);
        }
    }
}
