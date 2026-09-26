<?php
$content = file_get_contents('tests/Feature/Modules/Workload/IssueApiSecurityTest.php');

$replacements = [
    "'project_id' => \$this->projectId," => "",
    "'is_system' => false," => "'is_active' => true, 'slug' => 'task',",
    "'workflow_id' => \$this->workflowId," => "",
    "'order' => 1," => "",
    "DB::table('workflows')->insert([" => "/*",
    "'updated_at' => now(),\n    ]);" => "'updated_at' => now(),\n    ]);*/", // Note: This might comment out wrong things, let's just do it cleanly with sed or rewrite
];
// Wait, I will just recreate the file to be safe.
