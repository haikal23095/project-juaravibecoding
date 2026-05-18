<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiDetectionController extends Controller
{
    public function detect(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:10240', // max 10MB
        ]);

        try {
            $image = $request->file('image');
            
            $aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:8000/api/predict');
            
            // Forward the image to the AI microservice
            $response = Http::attach(
                'file', file_get_contents($image->getRealPath()), $image->getClientOriginalName()
            )->post($aiServiceUrl);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan prediksi dari AI Microservice.',
                'error' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('AI Detection Error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghubungi AI Microservice.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
