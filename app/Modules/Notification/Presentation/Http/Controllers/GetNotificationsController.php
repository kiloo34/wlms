<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Controllers;

use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class GetNotificationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        /** @var LengthAwarePaginator<int, NotificationModel> $paginator */
        $paginator = NotificationModel::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = NotificationModel::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'unread_count' => $unreadCount,
        ]);
    }
}
