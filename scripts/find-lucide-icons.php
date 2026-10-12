<?php

declare(strict_types=1);

// Prints the Lucide icon names referenced in the theme's PHP plus scripts/lucide-icons.json
// (for names built at runtime, which the scan can't see) as a JSON array.

require __DIR__ . '/lib-icon-scanner.php';

$repo_root = dirname(__DIR__);
$icon_names = [];

foreach (collect_php_files($repo_root) as $file_path) {
    $source = file_get_contents($file_path);

    if ($source === false || trim($source) === '') {
        continue;
    }

    foreach (extract_bracket_literals($source) as $literal) {
        $has_lucide_set = preg_match('/[\'"]set[\'"]\s*=>\s*[\'"]lucide[\'"]/', $literal) === 1;
        $has_name =
            preg_match('/[\'"]name[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $literal, $matches) === 1;

        if ($has_lucide_set && $has_name) {
            $icon_names[$matches[1]] = true;
        }
    }
}

foreach (load_json_array_file($repo_root . '/scripts/lucide-icons.json') as $extra_name) {
    if (is_string($extra_name) && trim($extra_name) !== '') {
        $icon_names[trim($extra_name)] = true;
    }
}

$result = array_keys($icon_names);
sort($result);

echo json_encode(array_values($result), JSON_UNESCAPED_SLASHES);
