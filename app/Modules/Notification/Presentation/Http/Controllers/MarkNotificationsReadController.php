<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Controllers;

use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class MarkNotificationsReadController
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $affected = DB::transaction(fn () =>
            NotificationModel::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()])
        );

        return response()->json([
            'message'  => 'Notifications marked as read.',
            'affected' => $affected,
        ]);
    }
}

