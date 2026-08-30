<?php

declare(strict_types=1);

// shadcn/ui's Accordion wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): JS-driven open/onValueChange state,
// manually wired ARIA (button[aria-expanded] + region[aria-labelledby]). Our PHP/no-JS equivalent
// uses native <details>/<summary> instead: the HTML Living Standard's `name` attribute on
// <details> makes the browser enforce "only one open at a time" natively -- zero JavaScript
// required for shadcn's `type="single"` behaviour (this theme has no JS framework wired up, see
// assets/js/ -- vanilla modules only, no Alpine/similar).
//
// <details>/<summary> already carry correct native disclosure semantics (implicit ARIA, native
// keyboard/focus handling), so no additional role/aria-expanded is added on top -- that would be
// redundant/conflicting ARIA on an already-semantic native element. Any CSS for the open/closed
// look must target the native `[open]` attribute (e.g. `details[open] { ... }`,
// `details[open] [data-slot="icon"] { transform: rotate(180deg); }`) -- NOT a custom data-state,
// which would go stale after the user's first click since there is no JS to keep it in sync.
//
// Supported config:
//   type          string   single | multiple (default: single) -- mirrors the Radix UI variant of
//                           shadcn's Accordion; re-checked live, the current Base UI default track
//                           has replaced this with a boolean `multiple` prop instead (no `type` at
//                           all). Kept as `type: single|multiple` here regardless, since it maps
//                           cleanly onto this component's actual implementation technique --
//                           whether one or several <details> share a `name` attribute -- and existing
//                           callers already use this vocabulary. "single": only one item open at a
//                           time (native <details name="..."> grouping). "multiple": items toggle
//                           independently (no name attribute).
//   items         array    required, list of item configs:
//     trigger         string   required. Visible trigger label (plain text, escaped)
//     content         string   required. Pre-rendered HTML for the panel body (caller's
//                                responsibility to escape/build, e.g. via typography.php --
//                                same convention as aspect-ratio.php's `content`)
//     value           string   optional. Rendered as the <details> `id` (deep-linking/CSS/JS
//                                hook); auto-generated via wp_unique_id() when omitted
//     default_open    bool     optional. Sets the native `open` attribute (default: false)
//     class           string   passthrough onto this item's <details>
//   heading_tag   string   '' | h2 | h3 | h4 | h5 | h6 (default: ''). When set, wraps the trigger
//                           label in that heading tag inside <summary> by nesting
//                           template-parts/base/typography.php -- the recommended a11y pattern
//                           for FAQ-style accordions (WAI-ARIA APG Accordion Pattern)
//   icon          array|false   icon.php config for the trigger's chevron indicator (default:
//                           ['name' => 'chevron-down', 'set' => 'lucide']); pass false to omit
//                           and rely on the native <summary> marker instead
//   class / attributes / data_attributes   passthrough onto the outer wrapper
//                           (<div data-slot="accordion">)
//
// Note: no per-item `disabled` yet -- <details>/<summary> has no native disabled attribute, and
// emulating it without JS (pointer-events:none + tabindex="-1" + matching CSS) has no concrete
// consumer yet. Deferred extension point, same spirit as image.php's attachment_id note.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$type = trim((string) ($config['type'] ?? 'single'));
$heading_tag = strtolower(trim((string) ($config['heading_tag'] ?? '')));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];
$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];

$allowed_types = ['single', 'multiple'];

if (!in_array($type, $allowed_types, true)) {
    $type = 'single';
}

$allowed_heading_tags = ['', 'h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($heading_tag, $allowed_heading_tags, true)) {
    $heading_tag = '';
}

if (array_key_exists('icon', $config) && $config['icon'] === false) {
    $icon_config = null;
} elseif (is_array($config['icon'] ?? null)) {
    $icon_config = $config['icon'];
} else {
    $icon_config = ['name' => 'chevron-down', 'set' => 'lucide'];
}

// Normalize + filter items; trigger and content are required, invalid items are silently
// skipped (fail-soft, consistent with the rest of the base components).
$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $trigger_text = trim((string) ($item_config['trigger'] ?? ''));
    $content = (string) ($item_config['content'] ?? '');

    if ($trigger_text === '' || trim($content) === '') {
        continue;
    }

    $items[] = [
        'trigger' => $trigger_text,
        'content' => $content,
        'value' => trim((string) ($item_config['value'] ?? '')),
        'default_open' => !empty($item_config['default_open']),
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

$group_name = 'hengegroup-theme-accordion-' . wp_unique_id();

$render_heading = static function (string $tag, string $text): string {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        // body-medium is font-normal by default (see typography.php) -- an accordion trigger
        // needs to stand out as a heading, hence the added emphasis here.
        'config' => [
            'variant' => 'body-medium',
            'tag' => $tag,
            'text' => $text,
            'class' => 'font-semibold',
        ],
    ]);

    return (string) ob_get_clean();
};

$icon_markup = $icon_config !== null ? hengegroup_theme_render_icon($icon_config) : '';

$items_markup = '';

foreach ($items as $item) {
    $item_value =
        $item['value'] !== ''
            ? $item['value']
            : 'hengegroup-theme-accordion-item-' . wp_unique_id();

    $trigger_markup =
        $heading_tag !== ''
            ? $render_heading($heading_tag, $item['trigger'])
            : esc_html($item['trigger']);

    $details_attributes = [];

    if ($type === 'single') {
        $details_attributes['name'] = $group_name;
    }

    $details_attributes['data-slot'] = 'accordion-item';
    $details_attributes['id'] = $item_value;

    if ($item['class'] !== '') {
        $details_attributes['class'] = $item['class'];
    }

    if ($item['default_open']) {
        $details_attributes['open'] = true;
    }

    $items_markup .= sprintf(
        '<details%1$s><summary data-slot="accordion-trigger">%2$s%3$s</summary><div data-slot="accordion-content">%4$s</div></details>',
        hengegroup_theme_render_attributes($details_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $item['content'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'accordion';
$wrapper_attributes['data-type'] = $type;

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
