<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Statuses
        $todo       = (string) Str::uuid();
        $inProgress = (string) Str::uuid();
        $inReview   = (string) Str::uuid();
        $done       = (string) Str::uuid();

        DB::table('statuses')->insert([
            ['id' => $todo,       'name' => 'To Do',       'slug' => 'todo',        'category' => 'TODO',        'color' => '#E2E8F0'],
            ['id' => $inProgress, 'name' => 'In Progress', 'slug' => 'in-progress', 'category' => 'IN_PROGRESS', 'color' => '#3B82F6'],
            ['id' => $inReview,   'name' => 'In Review',   'slug' => 'in-review',   'category' => 'IN_PROGRESS', 'color' => '#F59E0B'],
            ['id' => $done,       'name' => 'Done',        'slug' => 'done',        'category' => 'DONE',        'color' => '#10B981'],
        ]);

        // 2. Default Workflow
        $workflowId = (string) Str::uuid();
        DB::table('workflows')->insert([
            'id' => $workflowId, 'name' => 'Default Software Workflow', 'description' => 'Standard agile workflow', 'is_default' => true,
        ]);

        // 3. Workflow Transitions (the State Machine rules)
        DB::table('workflow_transitions')->insert([
            // Entry point: NULL → To Do (initial creation)
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => null,       'to_status_id' => $todo,       'name' => 'Create'],
            // Forward
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $todo,       'to_status_id' => $inProgress, 'name' => 'Start Progress'],
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $inProgress, 'to_status_id' => $inReview,   'name' => 'Send to Review'],
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $inReview,   'to_status_id' => $done,       'name' => 'Approve'],
            // Backward (reject/reopen)
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $inReview,   'to_status_id' => $inProgress, 'name' => 'Request Changes'],
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $inProgress, 'to_status_id' => $todo,       'name' => 'Stop Progress'],
            // Reopen from Done
            ['id' => (string) Str::uuid(), 'workflow_id' => $workflowId, 'from_status_id' => $done,       'to_status_id' => $inProgress, 'name' => 'Reopen'],
        ]);

        // 4. Issue Types
        DB::table('issue_types')->insert([
            ['id' => (string) Str::uuid(), 'name' => 'Epic',        'slug' => 'epic',        'icon' => 'layers',      'color' => '#8B5CF6', 'sort_order' => 1, 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Story',       'slug' => 'story',       'icon' => 'bookmark',    'color' => '#10B981', 'sort_order' => 2, 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Task',        'slug' => 'task',        'icon' => 'check-square','color' => '#3B82F6', 'sort_order' => 3, 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Bug',         'slug' => 'bug',         'icon' => 'bug',         'color' => '#EF4444', 'sort_order' => 4, 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Spike',       'slug' => 'spike',       'icon' => 'zap',         'color' => '#F59E0B', 'sort_order' => 5, 'is_active' => true],
        ]);

        // 5. Priorities
        DB::table('priorities')->insert([
            ['id' => (string) Str::uuid(), 'name' => 'Low',      'slug' => 'low',      'level' => 1, 'icon' => 'arrow-down',  'color' => '#94A3B8', 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Medium',   'slug' => 'medium',   'level' => 2, 'icon' => 'minus',       'color' => '#F59E0B', 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'High',     'slug' => 'high',     'level' => 3, 'icon' => 'arrow-up',    'color' => '#F97316', 'is_active' => true],
            ['id' => (string) Str::uuid(), 'name' => 'Critical', 'slug' => 'critical', 'level' => 4, 'icon' => 'zap',         'color' => '#EF4444', 'is_active' => true],
        ]);
    }
}
