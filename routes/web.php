<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/dashboard', 'dashboard')->name('dashboard');
    Route::inertia('/workspaces', 'Workspaces/Index')->name('workspaces.index');
    
    // Placeholder routes untuk fitur yang belum di-build UI-nya (DRY Principle)
    Route::get('/projects', \App\Modules\Workload\Presentation\Http\Controllers\ProjectIndexPageController::class)->name('projects.index');
    Route::get('/projects/{project}', \App\Modules\Workload\Presentation\Http\Controllers\ShowProjectPageController::class)->name('projects.show');
    Route::get('/issues', fn() => inertia('Issues/Index'))->name('issues.index');
    Route::get('/users', fn() => inertia('coming-soon', ['title' => 'Users']))->name('users.index');

    // Modul RBAC (Khusus Superadmin)
    Route::middleware('can:manage-rbac')->prefix('api/rbac')->group(function () {
        Route::get('/roles', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'index']);
        Route::post('/roles', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'store']);
        Route::get('/roles/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'show']);
        Route::put('/roles/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'update']);
        Route::delete('/roles/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'destroy']);
        Route::put('/roles/{id}/permissions', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'syncPermissions']);
        Route::put('/roles/{id}/menus', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\RoleController::class, 'syncMenus']);
        
        Route::get('/permissions', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\PermissionController::class, 'index']);
        Route::get('/menus', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\MenuController::class, 'index']);
        
        Route::get('/users', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\UserController::class, 'index']);
        Route::post('/users', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\UserController::class, 'store']);
        Route::put('/users/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\UserController::class, 'update']);
        
        Route::post('/user-roles/assign', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\UserRoleController::class, 'assign']);
        Route::post('/user-roles/revoke', [\App\Modules\Identity\Presentation\Http\Controllers\RBAC\UserRoleController::class, 'revoke']);
    });
    // Modul Organisasi (Struktur Level & Unit)
    Route::middleware('can:manage-rbac')->group(function () {
        Route::prefix('api/org-levels')->group(function () {
            Route::get('/', [\App\Modules\Identity\Presentation\Http\Controllers\OrgLevelController::class, 'index']);
            Route::post('/', [\App\Modules\Identity\Presentation\Http\Controllers\OrgLevelController::class, 'store']);
            Route::put('/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\OrgLevelController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\OrgLevelController::class, 'destroy']);
        });

        Route::prefix('api/org-units')->group(function () {
            Route::get('/', [\App\Modules\Identity\Presentation\Http\Controllers\OrgUnitController::class, 'index']);
            Route::post('/', [\App\Modules\Identity\Presentation\Http\Controllers\OrgUnitController::class, 'store']);
            Route::put('/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\OrgUnitController::class, 'update']);
            Route::delete('/{id}', [\App\Modules\Identity\Presentation\Http\Controllers\OrgUnitController::class, 'destroy']);
        });
    });
});

require __DIR__.'/settings.php';

use App\Http\Controllers\LocaleController;

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
