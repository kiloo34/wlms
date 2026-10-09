<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Jobs;

use App\Modules\Workload\Application\UseCases\ImportIssuesUseCase;
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

final class ImportIssuesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $filePath,
        public readonly string $reporterId
    ) {}

    public function handle(ImportIssuesUseCase $useCase): void
    {
        $import = new class($this->reporterId, $useCase) implements ToCollection, WithHeadingRow
        {
            public function __construct(
                private readonly string $reporterId,
                private readonly ImportIssuesUseCase $useCase
            ) {}

            public function collection(Collection $rows): void
            {
                $this->useCase->execute($rows, $this->reporterId);
            }
        };

        if (Storage::disk('local')->exists($this->filePath)) {
            Excel::import($import, Storage::disk('local')->path($this->filePath));
        } else {
            Log::warning("ImportIssuesJob: File not found {$this->filePath}");
        }
    }
}
