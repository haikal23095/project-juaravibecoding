<?php

namespace App\Http\Controllers;

use App\Models\WasteCategory;
use Illuminate\Http\JsonResponse;

class WasteCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => WasteCategory::all()
        ]);
    }
}
