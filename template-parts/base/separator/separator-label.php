<?php

declare(strict_types=1);

// NOT a shadcn/ui component -- shadcn's own Separator (see separator.php's own header) is a plain,
// content-less line. This is a Rule-2 implementation extension for a divider WITH inline content --
// an eyebrow-style section label, a plain "oder" word, or a bare accent-dot marker with no text at
// all -- modeled on the Claude-Design reference "Hengegroup"'s "Mit Beschriftung" section (same
// `.dc.html`-reference workflow as separator.php's own Phase-2 entry, see docs/entscheidungen.md
// for this component's entry). Stated plainly as an extension rather than dressed up as upstream
// vocabulary, same category as pagination-compact.php's own header.
//
// NOT the same technique as field-separator.php's own labeled divider (shadcn's own
// FieldSeparator: one absolutely-positioned line sitting behind a background-colour-erasing label
// span). That technique is scoped to sitting inside field-group.php's own layout context (relies on
// `-my-2`/its ancestor's actual background colour to visually "erase" the line behind the label,
// see field-separator.php's own header). This file instead composes two real separator.php lines
// with `flex-1` around the label, matching the reference's own layout exactly and working
// standalone against any surrounding background, not just field-group.php's own.
//
// Nests template-parts/base/separator/separator.php (once for the `start` position, twice flanking
// the label for `center`/the dot marker) via the same ob_start()/get_template_part()/ob_get_clean()
// buffering as kbd-group.php/pagination-compact.php.
//
// The eyebrow-style `start` label reuses the exact same classes as table-head.php's own header-cell
// look (`text-xs font-semibold tracking-widest text-muted-foreground uppercase`) -- both trace back
// to the same reference value (`letter-spacing:0.1em`, closest real Tailwind step is
// `tracking-widest`/0.1em exactly, see table-head.php's own header), one shared "eyebrow label"
// look instead of two independently-derived ones.
//
// Supported config:
//   label      string   optional. Omit (or leave empty) for a bare accent-dot divider with no text
//                        -- the reference's own third example (a small dot between two lines).
//                        Fixed henge-green colour (this project's brand accent, see tokens.css),
//                        not a configurable prop -- same fixed-colour choice as pagination.php's
//                        own active-page henge-green fill
//   position   string   start | center (default: 'start'). 'start': the eyebrow label followed by a
//                        single flex-1 line, matches the reference's "Technische Daten" example.
//                        'center': a plain label flanked by two flex-1 lines, matches the
//                        reference's "oder" example. Ignored (forced to a centered layout) when
//                        `label` is empty -- a bare dot marker has only one sensible layout
//   class / attributes / data_attributes   passthrough onto the outer wrapper, as in the other base
//                        parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$label = trim((string) ($config['label'] ?? ''));
$position = trim((string) ($config['position'] ?? 'start'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_positions = ['start', 'center'];

if (!in_array($position, $allowed_positions, true)) {
    $position = 'start';
}

if ($label === '') {
    // A bare dot marker only has one sensible layout, see file header above.
    $position = 'center';
}

$render_line = static function (): string {
    ob_start();
    get_template_part('template-parts/base/separator/separator', null, [
        'config' => ['class' => 'flex-1'],
    ]);

    return (string) ob_get_clean();
};

if ($label === '') {
    $middle_markup =
        '<span class="size-1.5 shrink-0 rounded-full bg-henge-green" aria-hidden="true"></span>';
} elseif ($position === 'start') {
    $middle_markup = sprintf(
        '<span class="shrink-0 text-xs font-semibold tracking-widest text-muted-foreground uppercase">%s</span>',
        esc_html($label),
    );
} else {
    $middle_markup = sprintf(
        '<span class="shrink-0 text-sm text-muted-foreground">%s</span>',
        esc_html($label),
    );
}

$content_markup =
    $position === 'start'
        ? $middle_markup . $render_line()
        : $render_line() . $middle_markup . $render_line();

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim(
    'flex items-center gap-3.5' . ($class_name !== '' ? ' ' . $class_name : ''),
);
$wrapper_attributes['data-slot'] = 'separator-label';
$wrapper_attributes['data-position'] = $position;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
