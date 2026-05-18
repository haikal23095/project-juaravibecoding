<?php

namespace App\Http\Controllers;

use App\Models\WasteBank;
use Illuminate\Http\JsonResponse;

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
}
