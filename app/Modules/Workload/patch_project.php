<?php
$content = file_get_contents('Domain/Entities/Project.php');

// Add WorkflowId use
$content = str_replace(
    'use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;',
    "use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;\nuse App\Modules\Workload\Domain\ValueObjects\WorkflowId;",
    $content
);

// Add to constructor
$content = preg_replace(
    '/private readonly DateTimeImmutable \$createdAt\n    \) \{\}/',
    "private readonly DateTimeImmutable \$createdAt,\n        private ?WorkflowId \$workflowId = null\n    ) {}",
    $content
);

// Update create method signature and instantiation
$content = preg_replace(
    '/new DateTimeImmutable\(\)\n        \);/',
    "new DateTimeImmutable(),\n            null\n        );",
    $content
);

// Update update method
$content = str_replace(
    'public function update(string $name, ?string $description, ?string $leadId, string $actorId): void',
    'public function update(string $name, ?string $description, ?string $leadId, ?WorkflowId $workflowId, string $actorId): void',
    $content
);

$content = preg_replace(
    '/\$this->leadId = \$leadId;/',
    "\$this->leadId = \$leadId;\n        if (\$workflowId !== null) {\n            \$this->workflowId = \$workflowId;\n        }",
    $content
);

// Add getter
$content = str_replace(
    'public function getLeadId(): ?string { return $this->leadId; }',
    "public function getLeadId(): ?string { return \$this->leadId; }\n    public function getWorkflowId(): ?WorkflowId { return \$this->workflowId; }",
    $content
);

file_put_contents('Domain/Entities/Project.php', $content);
