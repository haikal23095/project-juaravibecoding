<?php

namespace App\Http\Controllers;

use App\Models\PriceCatalog;
use App\Models\WasteCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PriceCatalogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('icons', 'public');
        }

        $cat = WasteCategory::where('name', strtolower($request->name))->first();
        
        if (!$cat) {
            $cat = WasteCategory::create([
                'name' => strtolower($request->name),
                'description' => $request->description ?? 'Kategori kustom ditambahkan manual',
                'is_default' => false,
                'is_active' => true,
                'icon_url' => $iconPath
            ]);
        } else if (!$cat->is_default) {
            if ($request->has('description')) $cat->description = $request->description;
            if ($iconPath) $cat->icon_url = $iconPath;
            $cat->save();
        }

        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        $pc = PriceCatalog::updateOrCreate(
            ['waste_bank_id' => $request->waste_bank_id, 'waste_category_id' => $cat->id],
            ['price_per_kg' => $request->price_per_kg, 'is_active' => $isActive]
        );

        return response()->json(['status' => 'success', 'data' => $pc]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $pc = PriceCatalog::find($id);
        if ($pc) {
            $pc->is_active = $request->is_active;
            $pc->save();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 404);
    }

    public function destroy($id): JsonResponse
    {
        $pc = PriceCatalog::find($id);
        if ($pc) {
            $pc->delete();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'error'], 404);
    }
}
