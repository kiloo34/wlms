<?php

namespace App\Modules\Identity\Presentation\Http\Controllers\RBAC;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PermissionController extends Controller
{
    public function index()
    {
        // Get all permissions grouped by resource or simply list them
        $permissions = PermissionModel::orderBy('resource')->orderBy('action')->get();
        return response()->json(['data' => $permissions]);
    }
}

