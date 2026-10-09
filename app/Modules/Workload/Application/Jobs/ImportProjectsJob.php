<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Jobs;

use App\Modules\Workload\Application\UseCases\ImportProjectsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

final class ImportProjectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $filePath,
        public readonly string $workspaceId
    ) {}

    public function handle(ImportProjectsUseCase $useCase): void
    {
        $import = new class($this->workspaceId, $useCase) implements ToCollection, WithHeadingRow
        {
            public function __construct(
                private readonly string $workspaceId,
                private readonly ImportProjectsUseCase $useCase
            ) {}

            public function collection(Collection $rows): void
            {
                $this->useCase->execute($rows, $this->workspaceId);
            }
        };

        if (Storage::disk('local')->exists($this->filePath)) {
            Excel::import($import, Storage::disk('local')->path($this->filePath));
        } else {
            Log::warning("ImportProjectsJob: File not found {$this->filePath}");
        }
    }
}
