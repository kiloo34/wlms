<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Collaboration\Presentation\Http\Controllers\AddCommentController;
use App\Modules\Collaboration\Presentation\Http\Controllers\EditCommentController;
use App\Modules\Collaboration\Presentation\Http\Controllers\GetIssueTimelineController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/issues/{issueId}/timeline', GetIssueTimelineController::class)
        ->name('collaboration.issues.timeline');
        
    Route::post('/issues/{issueId}/comments', AddCommentController::class)
        ->name('collaboration.issues.comments.add');
        
    Route::put('/issues/{issueId}/comments/{commentId}', EditCommentController::class)
        ->name('collaboration.issues.comments.edit');
});

