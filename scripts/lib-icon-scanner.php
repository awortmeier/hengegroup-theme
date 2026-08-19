<?php

declare(strict_types=1);

// Shared scanning helpers for the icon-set sync scripts (Lucide, Tabler, ...).
// Each icon set's own find-*.php only supplies the pattern-matching predicate;
// the file collection and PHP tokenizing live here once.

/**
 * @return string[]
 */
function collect_php_files(string $repo_root): array
{
    $files = glob($repo_root . '/*.php') ?: [];

    foreach (['inc', 'template-parts'] as $directory) {
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
 * Tokenizes PHP source and returns the "own text" of every bracket-delimited
 * literal ('[' ... ']' or '(' ... ')'), with nested sub-brackets masked out
 * (replaced by a placeholder). This isolates each array literal's direct
 * key => value pairs from those of any sibling or parent array, so callers
 * can pattern-match config arrays (e.g. icon.php's ['name' => ..., 'set' => ...])
 * without false cross-matches between unrelated icons in the same file.
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
 * Reads a JSON array file (e.g. scripts/lucide-icons.json) used to list extra
 * icons that should be synced even though nothing in the theme code
 * references them yet. Returns an empty array if the file is missing or
 * doesn't contain a JSON array — extra icons are optional, never required.
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
