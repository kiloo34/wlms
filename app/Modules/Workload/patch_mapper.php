<?php
$content = file_get_contents('Infrastructure/Persistence/Eloquent/Mappers/ProjectMapper.php');

$content = str_replace(
    'use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;',
    "use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;\nuse App\Modules\Workload\Domain\ValueObjects\WorkflowId;",
    $content
);

$content = preg_replace(
    '/\$propertyCreatedAt->setValue\(\$project, new DateTimeImmutable\(\$model->created_at->toDateTimeString\(\)\)\);/',
    "\$propertyCreatedAt->setValue(\$project, new DateTimeImmutable(\$model->created_at->toDateTimeString()));\n\n        if (\$model->workflow_id) {\n            \$propertyWorkflowId = \$reflection->getProperty('workflowId');\n            \$propertyWorkflowId->setValue(\$project, new WorkflowId(\$model->workflow_id));\n        }",
    $content
);

$content = preg_replace(
    '/\/\/ workflow_id skipped for now, bisa diupdate nanti/',
    "'workflow_id' => \$project->getWorkflowId()?->value,",
    $content
);

file_put_contents('Infrastructure/Persistence/Eloquent/Mappers/ProjectMapper.php', $content);
