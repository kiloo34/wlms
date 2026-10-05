<?php

declare(strict_types=1);

use App\Modules\Workload\Domain\Entities\Workspace;
use App\Modules\Workload\Domain\Events\WorkspaceCreated;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;

test('can create workspace with active status and record WorkspaceCreated event', function () {
    $id = new WorkspaceId('01923abc-0000-7000-8000-000000000000');

    $workspace = Workspace::create(
        $id,
        'group-uuid-123',
        'Engineering Workspace',
        'actor-uuid-456'
    );

    // Assert Entity State
    expect($workspace->getId()->value)->toBe('01923abc-0000-7000-8000-000000000000')
        ->and($workspace->getOwnerGroupId())->toBe('group-uuid-123')
        ->and($workspace->getName())->toBe('Engineering Workspace')
        ->and($workspace->getStatus())->toBe('ACTIVE');

    // Assert Domain Event Recorded
    $events = $workspace->flushEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(WorkspaceCreated::class)
        ->and($events[0]->workspaceId)->toBe('01923abc-0000-7000-8000-000000000000')
        ->and($events[0]->workspaceName)->toBe('Engineering Workspace')
        ->and($events[0]->actorId)->toBe('actor-uuid-456');

    // Buffer harus kosong setelah flush
    expect($workspace->flushEvents())->toBeEmpty();
});

test('can archive workspace and change status to ARCHIVED', function () {
    $workspace = Workspace::create(
        new WorkspaceId('01923abc-0000-7000-8000-000000000000'),
        'group-uuid',
        'Test',
        'actor-uuid'
    );

    $workspace->archive('actor-uuid');

    expect($workspace->getStatus())->toBe('ARCHIVED');
});
