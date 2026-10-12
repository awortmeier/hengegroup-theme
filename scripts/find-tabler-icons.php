<?php

declare(strict_types=1);

// Prints the Tabler icons referenced in the theme's PHP plus scripts/tabler-icons.json as a JSON
// array of {name, variant}. JSON entries: a name (outline) or {"name", "variant"}.

require __DIR__ . '/lib-icon-scanner.php';

$repo_root = dirname(__DIR__);
$found = [];

foreach (collect_php_files($repo_root) as $file_path) {
    $source = file_get_contents($file_path);

    if ($source === false || trim($source) === '') {
        continue;
    }

    foreach (extract_bracket_literals($source) as $literal) {
        $has_set =
            preg_match(
                '/[\'"]set[\'"]\s*=>\s*[\'"]tabler\/(outline|filled)[\'"]/',
                $literal,
                $set_matches,
            ) === 1;
        $has_name =
            preg_match('/[\'"]name[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $literal, $name_matches) ===
            1;

        if (!$has_set || !$has_name) {
            continue;
        }

        $variant = $set_matches[1];
        $name = $name_matches[1];
        $found[$variant . '/' . $name] = ['name' => $name, 'variant' => $variant];
    }
}

foreach (load_json_array_file($repo_root . '/scripts/tabler-icons.json') as $extra_icon) {
    if (is_string($extra_icon)) {
        $name = trim($extra_icon);
        $variant = 'outline';
    } elseif (is_array($extra_icon) && isset($extra_icon['name'])) {
        $name = trim((string) $extra_icon['name']);
        $variant =
            isset($extra_icon['variant']) && $extra_icon['variant'] === 'filled'
                ? 'filled'
                : 'outline';
    } else {
        continue;
    }

    if ($name === '') {
        continue;
    }

    $found[$variant . '/' . $name] = ['name' => $name, 'variant' => $variant];
}

$result = array_values($found);

usort(
    $result,
    static fn(array $a, array $b): int => [$a['variant'], $a['name']] <=> [
        $b['variant'],
        $b['name'],
    ],
);

echo json_encode($result, JSON_UNESCAPED_SLASHES);
