<?php
$file = 'resources/css/app.css';
$content = file_get_contents($file);

// Light mode background -> off-white (Zinc 50: #fafafa)
$content = preg_replace('/--background: oklch\(1 0 0\);/', '--background: oklch(0.985 0 0);', $content, 1);
// Ensure card remains pure white
$content = preg_replace('/--card: oklch\(1 0 0\);/', '--card: oklch(1 0 0);', $content, 1);

// Dark mode background -> very dark (Zinc 950: #09090b)
// We will replace the dark mode --background and --card
$content = preg_replace('/\.dark \{.*?--background: oklch\(0\.13 0 0\);/s', ".dark {\n    --background: oklch(0.14 0 0);", $content);
// Wait, regex might be tricky. Let's just str_replace the exact lines.
