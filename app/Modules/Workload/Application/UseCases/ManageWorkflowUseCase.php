<?php
declare(strict_types=1);
namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\WorkflowDTO;
use App\Modules\Workload\Domain\Entities\Workflow;
use App\Modules\Workload\Domain\Repositories\WorkflowRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\WorkflowId;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class ManageWorkflowUseCase
{
    public function __construct(private WorkflowRepositoryInterface $repository) {}

    public function create(WorkflowDTO $dto): Workflow
    {
        return DB::transaction(function () use ($dto) {
            $workflow = Workflow::create(
                new WorkflowId((string) Str::uuid()),
                $dto->name,
                $dto->description,
                $dto->isDefault
            );
            $this->repository->save($workflow);
            return $workflow;
        });
    }

    public function update(string $id, WorkflowDTO $dto): Workflow
    {
        return DB::transaction(function () use ($id, $dto) {
            $workflow = $this->repository->findById(new WorkflowId($id));
            if (!$workflow) throw new \Exception("Workflow not found");

            $workflow->update($dto->name, $dto->description, $dto->isDefault);
            $this->repository->save($workflow);
            return $workflow;
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {
            $this->repository->delete(new WorkflowId($id));
        });
    }

    public function getAll(): array
    {
        return $this->repository->findAll();
    }
}
