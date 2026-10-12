<?php

declare(strict_types=1);

// Shared scanning helpers for find-*-icons.php.

/**
 * @return string[]
 */
function collect_php_files(string $repo_root): array
{
    $files = glob($repo_root . '/*.php') ?: [];

    foreach (['inc', 'template-parts', 'woocommerce'] as $directory) {
        $absolute_directory = $repo_root . '/' . $directory;

        if (!is_dir($absolute_directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute_directory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file_info) {
            if ($file_info->isFile() && strtolower($file_info->getExtension()) === 'php') {
                $files[] = $file_info->getPathname();
            }
        }
    }

    return $files;
}

/**
 * Own text of every [...] / (...) literal with nested brackets masked, so each array's key => value
 * pairs can be matched without cross-matching sibling arrays.
 *
 * @return string[]
 */
function extract_bracket_literals(string $source): array
{
    $tokens = token_get_all($source);
    $stack = [];
    $current = '';
    $literals = [];

    foreach ($tokens as $token) {
        $text = is_array($token) ? $token[1] : $token;

        if ($text === '[' || $text === '(') {
            $stack[] = $current;
            $current = '';
            continue;
        }

        if ($text === ']' || $text === ')') {
            $literals[] = $current;
            $current = (array_pop($stack) ?? '') . '__ARR__';
            continue;
        }

        $current .= $text;
    }

    return $literals;
}

/**
 * [] when the file is missing or not a JSON array.
 *
 * @return array<int, mixed>
 */
function load_json_array_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? array_values($decoded) : [];
}
