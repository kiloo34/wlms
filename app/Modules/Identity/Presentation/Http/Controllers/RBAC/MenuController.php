<?php

namespace App\Modules\Identity\Presentation\Http\Controllers\RBAC;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        try {
            $menus = DB::table('menus')->orderBy('sort_order')->get();
            return response()->json(['data' => $menus]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
