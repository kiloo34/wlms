<?php

$content = file_get_contents('phpstan-report.json');
$start = strpos($content, '{');
if ($start !== false) {
    $content = substr($content, $start);
}
$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'JSON Decode Error: '.json_last_error_msg()."\n";
}
if (empty($data['files'])) {
    echo "No files found in JSON.\n";
}
foreach ($data['files'] ?? [] as $file => $errors) {
    echo "\n=== ".str_replace('/Users/robileksono/Sites/wlms/', '', $file)." ===\n";
    foreach ($errors['messages'] as $e) {
        echo "Line {$e['line']}: {$e['message']}\n";
    }
}
