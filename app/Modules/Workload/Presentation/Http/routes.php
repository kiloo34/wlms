<?php

use App\Modules\Workload\Presentation\Http\Controllers\CreateWorkspaceController;
use App\Modules\Workload\Presentation\Http\Controllers\GetWorkspacesController;
use App\Modules\Workload\Presentation\Http\Controllers\UpdateWorkspaceController;
use App\Modules\Workload\Presentation\Http\Controllers\ArchiveWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('workspaces')->group(function () {
    Route::post('/', CreateWorkspaceController::class);
    Route::get('/', GetWorkspacesController::class);
    Route::put('/{id}', UpdateWorkspaceController::class);
    Route::delete('/{id}', ArchiveWorkspaceController::class);
    Route::get('/{workspaceId}/projects', \App\Modules\Workload\Presentation\Http\Controllers\GetProjectsController::class);
    
    // Workspace Members
    Route::get('/{workspace_id}/members', [\App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController::class, 'index']);
    Route::post('/{workspace_id}/members', [\App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController::class, 'store']);
    Route::put('/{workspace_id}/members/{user_id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController::class, 'update']);
    Route::delete('/{workspace_id}/members/{user_id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkspaceMemberController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('projects')->group(function () {
    Route::post('/', \App\Modules\Workload\Presentation\Http\Controllers\CreateProjectController::class);
    Route::put('/{id}', \App\Modules\Workload\Presentation\Http\Controllers\UpdateProjectController::class);
    Route::delete('/{id}', \App\Modules\Workload\Presentation\Http\Controllers\ArchiveProjectController::class);
    Route::get('/{id}/board', \App\Modules\Workload\Presentation\Http\Controllers\GetBoardController::class);
    Route::get('/{id}/backlog', \App\Modules\Workload\Presentation\Http\Controllers\GetBacklogController::class);
    Route::get('/{project_id}/issues', [\App\Modules\Workload\Presentation\Http\Controllers\IssueController::class, 'index']);
    Route::post('/{project_id}/issues', [\App\Modules\Workload\Presentation\Http\Controllers\IssueController::class, 'store']);
    Route::get('/{project_id}/activity', [\App\Modules\Workload\Presentation\Http\Controllers\ProjectActivityController::class, 'index']);
    Route::get('/{id}/sprints', \App\Modules\Workload\Presentation\Http\Controllers\GetProjectSprintsController::class);
});

Route::middleware(['auth:sanctum'])->prefix('sprints')->group(function () {
    Route::post('/', \App\Modules\Workload\Presentation\Http\Controllers\CreateSprintController::class);
    Route::put('/{id}', \App\Modules\Workload\Presentation\Http\Controllers\UpdateSprintController::class);
    Route::put('/{id}/start', \App\Modules\Workload\Presentation\Http\Controllers\StartSprintController::class);
    Route::put('/{id}/complete', \App\Modules\Workload\Presentation\Http\Controllers\CompleteSprintController::class);
    Route::get('/{sprint_id}/workload', \App\Modules\Workload\Presentation\Http\Controllers\GetSprintWorkloadController::class);
});

Route::middleware(['auth:sanctum'])->prefix('issues')->group(function () {
    Route::get('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\IssueController::class, 'show']);
    Route::put('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\IssueController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\IssueController::class, 'destroy']);
    Route::post('/', \App\Modules\Workload\Presentation\Http\Controllers\CreateIssueController::class);
    Route::post('/{id}/assign', \App\Modules\Workload\Presentation\Http\Controllers\AssignIssueController::class);
    Route::post('/{id}/transition', \App\Modules\Workload\Presentation\Http\Controllers\TransitionIssueController::class);
    Route::get('/{id}/transitions', \App\Modules\Workload\Presentation\Http\Controllers\GetValidTransitionsController::class);
    Route::post('/{id}/worklogs', \App\Modules\Workload\Presentation\Http\Controllers\LogWorkController::class);
    Route::get('/{id}/worklogs', \App\Modules\Workload\Presentation\Http\Controllers\GetIssueWorklogsController::class);
    Route::get('/{id}/comments', [\App\Modules\Workload\Presentation\Http\Controllers\IssueCommentController::class, 'index']);
    Route::post('/{id}/comments', [\App\Modules\Workload\Presentation\Http\Controllers\IssueCommentController::class, 'store']);

});

Route::middleware(['auth:sanctum'])->prefix('statuses')->group(function () {
    Route::get('/', [\App\Modules\Workload\Presentation\Http\Controllers\StatusController::class, 'index']);
    Route::post('/', [\App\Modules\Workload\Presentation\Http\Controllers\StatusController::class, 'store']);
    Route::put('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\StatusController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\StatusController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('workflows')->group(function () {
    Route::get('/', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowController::class, 'index']);
    Route::post('/', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowController::class, 'store']);
    Route::get('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowController::class, 'show']);
    Route::put('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowController::class, 'destroy']);

    Route::post('/{workflow_id}/transitions', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowTransitionController::class, 'store']);
    Route::delete('/{workflow_id}/transitions/{transition_id}', [\App\Modules\Workload\Presentation\Http\Controllers\WorkflowTransitionController::class, 'destroy']);
});
