<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Domain\Enums\WorkType;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengaturan jadwal pengingat: milik user sendiri, dan default organisasi (admin).
 */
final class ReminderSettingsController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload($this->user($request)));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate(ReminderSettings::validationRules());
        unset($validated['timezone']);

        ReminderSettings::saveForUser($user->id, $validated);

        return response()->json($this->payload($user));
    }

    public function reset(Request $request): JsonResponse
    {
        $user = $this->user($request);
        ReminderSettings::resetUser($user->id);

        return response()->json($this->payload($user));
    }

    public function updateDefault(Request $request): JsonResponse
    {
        $user = $this->user($request);
        if (! $this->canManageDefaults($user)) {
            abort(403, 'Unauthorized.');
        }

        ReminderSettings::saveGlobal($request->validate(ReminderSettings::validationRules()));

        return response()->json($this->payload($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UserModel $user): array
    {
        return [
            'data' => ReminderSettings::forUser($user->id),
            'defaults' => ReminderSettings::global(),
            'has_override' => ReminderSettings::hasUserOverride($user->id),
            'can_manage_defaults' => $this->canManageDefaults($user),
            'work_types' => array_map(
                fn (WorkType $type): array => ['value' => $type->value, 'label' => $type->label(), 'recurring' => $type->isRecurring()],
                WorkType::cases()
            ),
        ];
    }

    private function canManageDefaults(UserModel $user): bool
    {
        return $user->hasPermission('*')
            || $user->hasPermission('manage-rbac')
            || $user->hasPermission('workspaces:manage');
    }

    private function user(Request $request): UserModel
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
