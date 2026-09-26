<?php

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrgUnitModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrgUnitController extends Controller
{
    public function index(): JsonResponse
    {
        // Get all root nodes (where parent_id is null)
        $units = OrgUnitModel::with(['level', 'children' => function ($query) {
            $query->with(['level', 'children' => function ($q) {
                // Fetch up to arbitrary depth if needed, but since it's an API, 
                // recursive loading is often handled via nested relations or a closure.
                // Let's use a simpler recursive relation in the model or just load a few depths.
            }]);
        }])->whereNull('parent_id')->get();

        // Better way to load infinite depth in Eloquent without causing N+1 is eager loading
        // But for a simple tree structure, we can just load all and build the tree in memory
        
        $allUnits = OrgUnitModel::with('level')->get();
        $tree = $this->buildTree($allUnits);

        return response()->json(['data' => $tree]);
    }

    private function buildTree($elements, $parentId = null) {
        $branch = array();

        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $element->setRelation('children', collect($children));
                } else {
                    $element->setRelation('children', collect([]));
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:org_units,id',
            'org_level_id' => 'required|exists:org_levels,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:30|unique:org_units,code',
            'is_active' => 'boolean',
        ]);

        $unit = DB::transaction(function () use ($validated) {
            $newUnit = OrgUnitModel::create($validated);

            // Insert into org_unit_closures
            // Self
            DB::table('org_unit_closures')->insert([
                'ancestor_id' => $newUnit->id,
                'descendant_id' => $newUnit->id,
                'depth' => 0
            ]);

            // Ancestors
            if (!empty($validated['parent_id'])) {
                // Fetch all ancestors of parent and insert for new node
                $ancestors = DB::table('org_unit_closures')
                    ->where('descendant_id', $validated['parent_id'])
                    ->get();
                
                $inserts = [];
                foreach ($ancestors as $ancestor) {
                    $inserts[] = [
                        'ancestor_id' => $ancestor->ancestor_id,
                        'descendant_id' => $newUnit->id,
                        'depth' => $ancestor->depth + 1
                    ];
                }
                if (count($inserts) > 0) {
                    DB::table('org_unit_closures')->insert($inserts);
                }
            }

            return $newUnit;
        });

        $unit->load('level');

        return response()->json([
            'message' => 'Org Unit created successfully',
            'data' => $unit
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $unit = OrgUnitModel::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:org_units,id',
            'org_level_id' => 'sometimes|required|exists:org_levels,id',
            'name' => 'sometimes|required|string|max:150',
            'code' => 'sometimes|nullable|string|max:30|unique:org_units,code,' . $id,
            'is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($unit, $validated) {
            // Check if parent is actually changing
            $oldParentId = $unit->parent_id;
            $newParentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $oldParentId;

            // Prevent setting parent to itself
            if ($newParentId === $unit->id) {
                abort(422, 'Cannot set an organization unit as its own parent.');
            }

            // Update basic details
            $unit->update($validated);

            // Handle Subtree Move in Closure Table if parent_id changed
            if ($oldParentId !== $newParentId) {
                // Prevent circular references (cannot move to one of its own descendants)
                if ($newParentId) {
                    $isDescendant = DB::table('org_unit_closures')
                        ->where('ancestor_id', $unit->id)
                        ->where('descendant_id', $newParentId)
                        ->exists();
                    if ($isDescendant) {
                        abort(422, 'Cannot move unit under its own descendant.');
                    }
                }

                // 1. Delete paths that disconnect the subtree from its old ancestors
                // Delete where descendant is in the subtree AND ancestor is NOT in the subtree
                DB::statement("
                    DELETE FROM org_unit_closures 
                    WHERE descendant_id IN (SELECT descendant_id FROM (SELECT descendant_id FROM org_unit_closures WHERE ancestor_id = ?) AS tmpsub)
                    AND ancestor_id IN (SELECT ancestor_id FROM (SELECT ancestor_id FROM org_unit_closures WHERE descendant_id = ? AND ancestor_id != ?) AS tmpanc)
                ", [$unit->id, $unit->id, $unit->id]);

                // 2. Insert new paths connecting the new ancestors to the subtree
                if ($newParentId) {
                    DB::statement("
                        INSERT INTO org_unit_closures (ancestor_id, descendant_id, depth)
                        SELECT supertree.ancestor_id, subtree.descendant_id, supertree.depth + subtree.depth + 1
                        FROM org_unit_closures AS supertree
                        CROSS JOIN org_unit_closures AS subtree
                        WHERE supertree.descendant_id = ?
                        AND subtree.ancestor_id = ?
                    ", [$newParentId, $unit->id]);
                }
            }
        });

        $unit->load('level');

        return response()->json([
            'message' => 'Org Unit updated successfully',
            'data' => $unit
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $unit = OrgUnitModel::findOrFail($id);

        if ($unit->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete unit because it has child units'
            ], 422);
        }
        
        if ($unit->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete unit because it has assigned users'
            ], 422);
        }

        // DB Cascade on org_unit_closures should handle the closure table deletions automatically
        // as defined in the migration (onDelete cascade)
        $unit->delete();

        return response()->json([
            'message' => 'Org Unit deleted successfully'
        ]);
    }
}
