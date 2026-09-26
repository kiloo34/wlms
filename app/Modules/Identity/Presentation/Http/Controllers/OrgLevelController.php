<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrgLevelModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class OrgLevelController extends Controller
{
    public function index(): JsonResponse
    {
        $levels = OrgLevelModel::orderBy('depth')->get();
        return response()->json(['data' => $levels]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:org_levels,slug',
            'depth' => 'required|integer',
            'is_leaf' => 'boolean',
            'can_own_workspace' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $level = OrgLevelModel::create($validated);

        return response()->json([
            'message' => 'Org Level created successfully',
            'data' => $level
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $level = OrgLevelModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:org_levels,slug,' . $id,
            'depth' => 'sometimes|required|integer',
            'is_leaf' => 'boolean',
            'can_own_workspace' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $level->update($validated);

        return response()->json([
            'message' => 'Org Level updated successfully',
            'data' => $level
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $level = OrgLevelModel::findOrFail($id);
        
        // Optionally check for dependent org_units before deleting
        if ($level->units()->exists()) {
            return response()->json([
                'message' => 'Cannot delete level because it has related units'
            ], 422);
        }

        $level->delete();

        return response()->json([
            'message' => 'Org Level deleted successfully'
        ]);
    }
}
