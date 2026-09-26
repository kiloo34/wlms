<?php

declare(strict_types=1);

use App\Modules\Workload\Application\UseCases\CreateWorkspaceUseCase;
use App\Modules\Workload\Application\DTOs\CreateWorkspaceInput;
use App\Modules\Workload\Application\DTOs\WorkspaceOutput;
use App\Modules\Workload\Domain\Exceptions\WorkspaceCreationException;
use App\Modules\Workload\Domain\Repositories\WorkspaceRepositoryInterface;
use App\Modules\Workload\Domain\Config\WorkspaceSettingsInterface;

test('throws exception when group has reached max workspaces limit', function () {
    // 1. Arrange: Mocks
    $repoMock = Mockery::mock(WorkspaceRepositoryInterface::class);
    $repoMock->shouldReceive('countByGroupId')->with('group-123')->andReturn(10);

    $settingsMock = Mockery::mock(WorkspaceSettingsInterface::class);
    $settingsMock->shouldReceive('getMaxWorkspacesPerGroup')->andReturn(10);

    $useCase = new CreateWorkspaceUseCase($repoMock, $settingsMock);
    $input = new CreateWorkspaceInput('uuid-ws', 'group-123', 'Project A', 'user-123');

    // 2. Act & Assert: Exception harus dilempar
    expect(fn () => $useCase->execute($input))
        ->toThrow(WorkspaceCreationException::class, 'Workspace limit of');
});

test('successfully creates workspace and returns DTO when under limit', function () {
    // 1. Arrange
    $repoMock = Mockery::mock(WorkspaceRepositoryInterface::class);
    $repoMock->shouldReceive('countByGroupId')->with('group-123')->andReturn(2);
    $repoMock->shouldReceive('save')->once();

    $settingsMock = Mockery::mock(WorkspaceSettingsInterface::class);
    $settingsMock->shouldReceive('getMaxWorkspacesPerGroup')->andReturn(10);

    $useCase = new CreateWorkspaceUseCase($repoMock, $settingsMock);

    // 2. Act
    $input = new CreateWorkspaceInput('01923abc-0000-7000-8000-000000000000', 'group-123', 'Project Alpha', 'user-123');
    $output = $useCase->execute($input);

    // 3. Assert
    expect($output)->toBeInstanceOf(WorkspaceOutput::class)
        ->and($output->id)->toBe('01923abc-0000-7000-8000-000000000000')
        ->and($output->name)->toBe('Project Alpha')
        ->and($output->status)->toBe('ACTIVE')
        ->and($output->ownerGroupId)->toBe('group-123');
});
