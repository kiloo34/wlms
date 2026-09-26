<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\SprintModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

final class GetSprintWorkloadController
{
    public function __invoke(string $sprintId): JsonResponse
    {
        $sprint = SprintModel::with(['project.workspace.members'])->findOrFail($sprintId);
        
        $start = $sprint->start_date ? Carbon::parse($sprint->start_date) : Carbon::now();
        $end = $sprint->end_date ? Carbon::parse($sprint->end_date) : $start->copy()->addDays(14);

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        $weekdays = $start->diffInDaysFiltered(fn (Carbon $date) => $date->isWeekday(), $end);
        if ($end->isWeekday()) {
            $weekdays++;
        }

        $workspace = $sprint->project->workspace;

        $members = $workspace->members;

        $issues = IssueModel::where('sprint_id', $sprintId)->get();

        $data = [];

        foreach ($members as $member) {
            $dailyCapacity = (int) ($member->pivot->daily_capacity_hours ?? 8);
            $capacitySeconds = $weekdays * $dailyCapacity * 3600;
            $allocatedSeconds = $issues->where('assignee_id', $member->id)->sum('original_estimate_seconds');

            $data[] = [
                'user_id' => $member->id,
                'name' => $member->name,
                'avatar' => $member->avatar_url ?? null,
                'capacity_seconds' => $capacitySeconds,
                'allocated_seconds' => (int) $allocatedSeconds,
            ];
        }

        return response()->json(['data' => $data]);
    }
}

