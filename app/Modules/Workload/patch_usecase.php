<?php
$content = file_get_contents('Application/UseCases/UpdateProjectUseCase.php');

$content = str_replace(
    'use App\Modules\Workload\Domain\ValueObjects\ProjectId;',
    "use App\Modules\Workload\Domain\ValueObjects\ProjectId;\nuse App\Modules\Workload\Domain\ValueObjects\WorkflowId;",
    $content
);

$content = preg_replace(
    '/\$input->leadId,\n                \$input->actorUserId\n            \);/',
    "\$input->leadId,\n                \$input->workflowId ? new WorkflowId(\$input->workflowId) : null,\n                \$input->actorUserId\n            );",
    $content
);

file_put_contents('Application/UseCases/UpdateProjectUseCase.php', $content);
