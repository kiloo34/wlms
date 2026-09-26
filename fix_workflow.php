<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkflowTransitionModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\StatusModel;
use Illuminate\Support\Str;

$todoStatus = StatusModel::where('slug', 'to-do')->first();
$inProgressStatus = StatusModel::where('slug', 'in-progress')->first();
$doneStatus = StatusModel::where('slug', 'done')->first();

if (!$todoStatus) {
    echo "Statuses not found\n";
    exit(1);
}

$workflow = WorkflowModel::where('is_default', true)->first();

if (!$workflow) {
    $workflow = WorkflowModel::create([
        'id' => (string) Str::uuid(),
        'name' => 'System Default Workflow',
        'description' => 'Standard workflow',
        'is_default' => true,
        'is_active' => true,
    ]);
    echo "Workflow created.\n";
} else {
    echo "Workflow exists.\n";
}

// Ensure initial transition
$initialTrans = WorkflowTransitionModel::where('workflow_id', $workflow->id)
    ->whereNull('from_status_id')
    ->where('to_status_id', $todoStatus->id)
    ->first();

if (!$initialTrans) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => null,
        'to_status_id' => $todoStatus->id,
        'name' => 'Create',
    ]);
    echo "Initial transition created.\n";
}

// Ensure To Do -> In Progress transition
if (!WorkflowTransitionModel::where('workflow_id', $workflow->id)->where('from_status_id', $todoStatus->id)->where('to_status_id', $inProgressStatus->id)->exists()) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => $todoStatus->id,
        'to_status_id' => $inProgressStatus->id,
        'name' => 'Start Progress',
    ]);
    echo "To Do -> In Progress transition created.\n";
}

// Ensure In Progress -> Done transition
if (!WorkflowTransitionModel::where('workflow_id', $workflow->id)->where('from_status_id', $inProgressStatus->id)->where('to_status_id', $doneStatus->id)->exists()) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => $inProgressStatus->id,
        'to_status_id' => $doneStatus->id,
        'name' => 'Complete',
    ]);
    echo "In Progress -> Done transition created.\n";
}

// Ensure In Progress -> To Do transition
if (!WorkflowTransitionModel::where('workflow_id', $workflow->id)->where('from_status_id', $inProgressStatus->id)->where('to_status_id', $todoStatus->id)->exists()) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => $inProgressStatus->id,
        'to_status_id' => $todoStatus->id,
        'name' => 'Stop Progress',
    ]);
    echo "In Progress -> To Do transition created.\n";
}

// Ensure To Do -> Done transition
if (!WorkflowTransitionModel::where('workflow_id', $workflow->id)->where('from_status_id', $todoStatus->id)->where('to_status_id', $doneStatus->id)->exists()) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => $todoStatus->id,
        'to_status_id' => $doneStatus->id,
        'name' => 'Direct Complete',
    ]);
    echo "To Do -> Done transition created.\n";
}

// Ensure Done -> To Do transition
if (!WorkflowTransitionModel::where('workflow_id', $workflow->id)->where('from_status_id', $doneStatus->id)->where('to_status_id', $todoStatus->id)->exists()) {
    WorkflowTransitionModel::create([
        'id' => (string) Str::uuid(),
        'workflow_id' => $workflow->id,
        'from_status_id' => $doneStatus->id,
        'to_status_id' => $todoStatus->id,
        'name' => 'Reopen',
    ]);
    echo "Done -> To Do transition created.\n";
}

echo "Done.\n";
