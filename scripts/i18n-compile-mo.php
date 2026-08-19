<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/i18n-compile-mo.php <file.po> [<file.po> ...]\n");
    exit(1);
}

function decode_po_string(string $value): string
{
    return stripcslashes($value);
}

function parse_po_file(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        // CLI build script, no WP runtime/HTML output, no esc_* helpers loaded; message only
        // ever surfaces via an uncaught-exception dump to STDERR.
        throw new RuntimeException("Unable to read PO file: {$path}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
    }

    $entries = [];
    $msgid = null;
    $msgstr = null;
    $current = null;

    $flush = static function () use (&$entries, &$msgid, &$msgstr): void {
        if ($msgid === null || $msgstr === null) {
            return;
        }

        $entries[$msgid] = $msgstr;
        $msgid = null;
        $msgstr = null;
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            $flush();
            $current = null;
            continue;
        }

        if (str_starts_with($trimmed, '#')) {
            continue;
        }

        if (preg_match('/^msgid\s+"(.*)"$/', $trimmed, $matches) === 1) {
            $flush();
            $msgid = decode_po_string($matches[1]);
            $msgstr = '';
            $current = 'msgid';
            continue;
        }

        if (preg_match('/^msgstr\s+"(.*)"$/', $trimmed, $matches) === 1) {
            $msgstr = decode_po_string($matches[1]);
            $current = 'msgstr';
            continue;
        }

        if (preg_match('/^"(.*)"$/', $trimmed, $matches) === 1) {
            if ($current === 'msgid') {
                $msgid .= decode_po_string($matches[1]);
            } elseif ($current === 'msgstr') {
                $msgstr .= decode_po_string($matches[1]);
            }
        }
    }

    $flush();

    ksort($entries, SORT_STRING);

    return $entries;
}

function compile_mo(array $entries): string
{
    $ids = [];
    $strings = [];

    foreach ($entries as $msgid => $msgstr) {
        $ids[] = $msgid;
        $strings[] = $msgstr;
    }

    $count = count($entries);
    $offsetOriginals = 28;
    $offsetTranslations = $offsetOriginals + $count * 8;

    $originalTable = '';
    $translationTable = '';
    $originalsBlock = '';
    $translationsBlock = '';

    $currentOriginalOffset = $offsetTranslations + $count * 8;
    $currentTranslationOffset = $currentOriginalOffset;

    foreach ($ids as $index => $id) {
        $originalsBlock .= $id . "\0";
        $currentTranslationOffset += strlen($id) + 1;
    }

    foreach ($strings as $string) {
        $translationsBlock .= $string . "\0";
    }

    $runningOriginalOffset = $offsetTranslations + $count * 8;
    foreach ($ids as $id) {
        $originalTable .= pack('VV', strlen($id), $runningOriginalOffset);
        $runningOriginalOffset += strlen($id) + 1;
    }

    $runningTranslationOffset = $currentTranslationOffset;
    foreach ($strings as $string) {
        $translationTable .= pack('VV', strlen($string), $runningTranslationOffset);
        $runningTranslationOffset += strlen($string) + 1;
    }

    return pack('V7', 0x950412de, 0, $count, $offsetOriginals, $offsetTranslations, 0, 0) .
        $originalTable .
        $translationTable .
        $originalsBlock .
        $translationsBlock;
}

foreach (array_slice($argv, 1) as $poFile) {
    $poPath = realpath($poFile);
    if ($poPath === false) {
        // See justification on parse_po_file()'s RuntimeException above (CLI-only script, no
        // esc_* helpers loaded).
        throw new RuntimeException("PO file not found: {$poFile}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
    }

    $entries = parse_po_file($poPath);
    $moPath = preg_replace('/\.po$/i', '.mo', $poPath);
    if (!is_string($moPath)) {
        // See justification on parse_po_file()'s RuntimeException above (CLI-only script, no
        // esc_* helpers loaded).
        throw new RuntimeException("Could not determine MO path for: {$poPath}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
    }

    file_put_contents($moPath, compile_mo($entries));
    fwrite(STDOUT, "Compiled {$moPath}\n");
}
