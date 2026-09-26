<?php
$files = [
    'tests/Feature/Workload/ProjectManagementTest.php',
    'tests/Feature/Workload/SprintManagementTest.php'
];

$roleCode = <<<CODE
    \$role = \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\RoleModel::firstOrCreate(['name' => 'Superadmin'], ['id' => \Illuminate\Support\Str::uuid(), 'scope' => 'GLOBAL']);
    \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserRoleModel::create(['id' => \Illuminate\Support\Str::uuid(), 'user_id' => \$user->id, 'role_id' => \$role->id]);
CODE;

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'Superadmin') === false) {
        $content = preg_replace('/\$user = UserModel::factory\(\)->create\([^)]*\);/', "$0\n$roleCode", $content);
        file_put_contents($file, $content);
    }
}
