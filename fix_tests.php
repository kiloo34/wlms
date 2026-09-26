<?php
$files = [
    'tests/Feature/Workload/IssueManagementTest.php',
    'tests/Feature/Modules/Workload/CreateWorkspaceApiSecurityTest.php',
    'tests/Feature/Workload/SprintManagementTest.php',
    'tests/Feature/Workload/ProjectManagementTest.php',
    'tests/Feature/Modules/Collaboration/CommentManagementTest.php',
];

$roleCode = <<<CODE
    \$role = \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => \Illuminate\Support\Str::uuid(), 'scope' => 'GLOBAL']);
    \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => \$this->user->id, 'role_id' => \$role->id]);
CODE;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'Superadmin') === false) {
        $content = preg_replace('/\$this->user = UserModel::factory\(\)->create\([^)]*\);/', "$0\n$roleCode", $content);
        file_put_contents($file, $content);
    }
}
