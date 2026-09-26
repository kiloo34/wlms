<?php
$files = [
    'tests/Feature/Workload/IssueManagementTest.php',
    'tests/Feature/Modules/Workload/CreateWorkspaceApiSecurityTest.php',
    'tests/Feature/Workload/SprintManagementTest.php',
    'tests/Feature/Workload/ProjectManagementTest.php',
    'tests/Feature/Modules/Collaboration/CommentManagementTest.php',
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace('\'role_id\' => $role->id]);', "'role_id' => \$role->id]);\n    if (isset(\$this->user)) { \$this->user->refresh(); } elseif (isset(\$user)) { \$user->refresh(); }", $content);
    file_put_contents($file, $content);
}
