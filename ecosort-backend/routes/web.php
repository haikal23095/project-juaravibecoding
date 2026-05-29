<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'EcoSort API Service is running perfectly!',
        'database_connection' => 'connected',
        'timestamp' => now()->toIso8601String()
    ]);
});

