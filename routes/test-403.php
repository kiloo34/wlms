<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/test-403', function (Request $request) {
    $user = $request->user();
    if (! $user) {
        return 'No user';
    }

    return [
        'id' => $user->id,
        'class' => get_class($user),
        'hasPermission' => $user->hasPermission('issues:manage'),
        'roles' => $user->userRoles->map(fn ($r) => $r->role->name),
    ];
})->middleware('auth:sanctum');
