<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WasteBankController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AiDetectionController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/google', [AuthController::class, 'googleAuth']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// Waste Bank
Route::get('/waste-banks', [WasteBankController::class, 'index']);

// Transactions
Route::post('/transactions', [TransactionController::class, 'store']);
Route::patch('/transactions/{id}/status', [TransactionController::class, 'updateStatus']); // For 'bank_sampah' manager

// AI Detection
Route::post('/ai/detect', [AiDetectionController::class, 'detect']);

// Users
Route::get('/users', function () {
    return response()->json([
        'status' => 'success',
        'data' => \App\Models\User::all()
    ]);
});

// Categories
Route::get('/waste-categories', function () {
    return response()->json([
        'status' => 'success',
        'data' => \App\Models\WasteCategory::all()
    ]);
});

// Price Catalogs
Route::post('/price-catalogs', function (Request $request) {
    $iconPath = null;
    if ($request->hasFile('icon')) {
        $iconPath = $request->file('icon')->store('icons', 'public');
    }

    $cat = \App\Models\WasteCategory::where('name', strtolower($request->name))->first();
    
    if (!$cat) {
        $cat = \App\Models\WasteCategory::create([
            'name' => strtolower($request->name),
            'description' => $request->description ?? 'Kategori kustom ditambahkan manual',
            'is_default' => false,
            'is_active' => true,
            'icon_url' => $iconPath
        ]);
    } else if (!$cat->is_default) {
        // Update description or icon if it's a custom category
        if ($request->has('description')) $cat->description = $request->description;
        if ($iconPath) $cat->icon_url = $iconPath;
        $cat->save();
    }

    // Handle boolean conversion from FormData (which sends strings like "true" or "false")
    $isActive = true;
    if ($request->has('is_active')) {
        $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
    }

    $pc = \App\Models\PriceCatalog::updateOrCreate(
        ['waste_bank_id' => $request->waste_bank_id, 'waste_category_id' => $cat->id],
        ['price_per_kg' => $request->price_per_kg, 'is_active' => $isActive]
    );

    return response()->json(['status' => 'success', 'data' => $pc]);
});

Route::patch('/price-catalogs/{id}', function (Request $request, $id) {
    $pc = \App\Models\PriceCatalog::find($id);
    if ($pc) {
        $pc->is_active = $request->is_active;
        $pc->save();
        return response()->json(['status' => 'success']);
    }
    return response()->json(['status' => 'error'], 404);
});

Route::delete('/price-catalogs/{id}', function ($id) {
    $pc = \App\Models\PriceCatalog::find($id);
    if ($pc) {
        // Option 1: Just delete it
        $pc->delete();
        return response()->json(['status' => 'success']);
    }
    return response()->json(['status' => 'error'], 404);
});
