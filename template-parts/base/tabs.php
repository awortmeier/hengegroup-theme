<?php

declare(strict_types=1);

// Tabs on visually hidden radios + <label> (works without JS). Panels are switched by
// :has() + :nth-child() rules in app.css (one per position). tabs.js upgrades to
// tablist/tab/tabpanel with roving tabindex and arrow keys; visibility stays CSS.
//
// Config:
//   items         array    required, each { value (required), trigger, icon, badge (badge.php
//                          config), content (required), disabled, aria_label (icon-only), class }
//   value         string   active tab, default first
//   orientation   string   horizontal (default) | vertical
//   variant       string   default (segmented) | line
//   name          string   shared radio name, default wp_unique_id()
//   disabled      bool     all triggers; also data-disabled on the list
//   aria_label    string   name of the list
//   class / attributes / data_attributes

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$active_value = trim((string) ($config['value'] ?? ''));
$orientation = trim((string) ($config['orientation'] ?? 'horizontal'));
$variant = trim((string) ($config['variant'] ?? 'default'));
$name = trim((string) ($config['name'] ?? ''));
$group_disabled = !empty($config['disabled']);
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_orientations = ['horizontal', 'vertical'];

if (!in_array($orientation, $allowed_orientations, true)) {
    $orientation = 'horizontal';
}

$allowed_variants = ['default', 'line'];

if (!in_array($variant, $allowed_variants, true)) {
    $variant = 'default';
}

$root_classes =
    'flex flex-col gap-4 data-[orientation=vertical]:flex-row ' .
    'data-[orientation=vertical]:items-start data-[orientation=vertical]:gap-8';

$is_vertical = $orientation === 'vertical';

if ($variant === 'line') {
    $list_variant_classes = $is_vertical
        ? 'w-fit flex-col items-start gap-1 self-stretch border-l border-border'
        : 'w-full items-center gap-6 border-b border-border';
} else {
    $list_variant_classes = $is_vertical
        ? 'w-fit flex-col items-stretch gap-1 rounded-xl border border-border bg-card p-1 shadow-xs'
        : 'w-fit items-center gap-1 rounded-xl border border-border bg-card p-1 shadow-xs';
}

$list_classes = 'inline-flex ' . $list_variant_classes;

// peer-checked:hover: keeps the active look on hover (plain hover: would otherwise win).
$trigger_base_classes =
    'relative inline-flex cursor-pointer items-center justify-center gap-1.5 whitespace-nowrap ' .
    'text-sm font-medium text-muted-foreground outline-none transition-colors select-none ' .
    "[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 " .
    'peer-focus-visible:border-ring peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50 ' .
    'focus-visible:border-ring focus-visible:outline-none focus-visible:ring-[3px] ' .
    'focus-visible:ring-ring/50 peer-disabled:pointer-events-none ' .
    'peer-disabled:cursor-not-allowed peer-disabled:opacity-50 hover:text-foreground';

if ($variant === 'line') {
    $trigger_variant_classes = $is_vertical
        ? 'justify-start border-l-2 border-transparent -ml-px px-4 py-2'
        : 'border-b-2 border-transparent -mb-px px-1 py-3';

    $trigger_variant_classes .=
        ' peer-checked:border-henge-green peer-checked:font-semibold ' .
        'peer-checked:text-foreground peer-checked:hover:text-foreground';
} else {
    $trigger_variant_classes =
        'rounded-lg px-3 py-1.5 peer-checked:bg-grey-dark peer-checked:font-semibold ' .
        'peer-checked:text-grey-dark-foreground peer-checked:shadow-xs ' .
        'peer-checked:hover:bg-grey-dark peer-checked:hover:text-grey-dark-foreground';
}

$trigger_classes = trim($trigger_base_classes . ' ' . $trigger_variant_classes);

$panels_classes = $is_vertical ? 'min-w-0 flex-1' : '';

$items = [];

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $value = trim((string) ($item_config['value'] ?? ''));
    $content = (string) ($item_config['content'] ?? '');
    $trigger_text = trim((string) ($item_config['trigger'] ?? ''));
    $icon_config = is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null;
    $badge_config = is_array($item_config['badge'] ?? null) ? $item_config['badge'] : null;

    if ($value === '' || trim($content) === '') {
        continue;
    }

    if ($trigger_text === '' && $icon_config === null) {
        continue;
    }

    $items[] = [
        'value' => $value,
        'content' => $content,
        'trigger' => $trigger_text,
        'icon' => $icon_config,
        'badge' => $badge_config,
        'disabled' => !empty($item_config['disabled']),
        'aria_label' => trim((string) ($item_config['aria_label'] ?? '')),
        'class' => trim((string) ($item_config['class'] ?? '')),
    ];
}

if ($items === []) {
    return;
}

$item_values = array_column($items, 'value');

if (!in_array($active_value, $item_values, true)) {
    $active_value = $item_values[0];
}

if ($name === '') {
    $name = 'hengegroup-theme-tabs-' . wp_unique_id();
}

$render_badge = static function (array $badge_config): string {
    ob_start();
    get_template_part('template-parts/base/badge', null, ['config' => $badge_config]);

    return (string) ob_get_clean();
};

$triggers_markup = '';
$panels_markup = '';

foreach ($items as $item) {
    $unique = wp_unique_id();
    $trigger_id = 'hengegroup-theme-tabs-trigger-' . $unique;
    $content_id = 'hengegroup-theme-tabs-content-' . $unique;
    $is_checked = $item['value'] === $active_value;
    $is_disabled = $group_disabled || $item['disabled'];
    $is_icon_only = $item['trigger'] === '' && $item['icon'] !== null;

    if ($item['icon'] !== null) {
        $icon_config = $item['icon'];

        if (!$is_icon_only) {
            $icon_config['data_attributes'] = array_merge(
                is_array($icon_config['data_attributes'] ?? null)
                    ? $icon_config['data_attributes']
                    : [],
                ['icon' => 'inline-start'],
            );
        }

        $icon_markup = hengegroup_theme_render_icon($icon_config);
    } else {
        $icon_markup = '';
    }

    if ($item['badge'] !== null) {
        $badge_config = $item['badge'];
        $badge_config['data_attributes'] = array_merge(
            is_array($badge_config['data_attributes'] ?? null)
                ? $badge_config['data_attributes']
                : [],
            ['badge' => 'inline-end'],
        );
        $badge_markup = $render_badge($badge_config);
    } else {
        $badge_markup = '';
    }

    $inner_html = $is_icon_only ? $icon_markup : $icon_markup . esc_html($item['trigger']);
    $inner_html .= $badge_markup;

    $input_attributes = [
        'type' => 'radio',
        'class' => 'peer sr-only',
        'data-slot' => 'tabs-trigger-input',
        'id' => $trigger_id,
        'name' => $name,
        'value' => $item['value'],
    ];

    if ($is_checked) {
        $input_attributes['checked'] = true;
    }

    if ($is_disabled) {
        $input_attributes['disabled'] = true;
        $input_attributes['data-disabled'] = 'true';
    }

    $label_attributes = [
        'class' => trim($trigger_classes . ($item['class'] !== '' ? ' ' . $item['class'] : '')),
    ];

    $label_attributes['data-slot'] = 'tabs-trigger';
    $label_attributes['data-value'] = $item['value'];
    $label_attributes['data-state'] = $is_checked ? 'active' : 'inactive';
    $label_attributes['for'] = $trigger_id;
    $label_attributes['aria-controls'] = $content_id;

    if ($is_disabled) {
        $label_attributes['data-disabled'] = 'true';
    }

    if ($item['aria_label'] !== '') {
        $label_attributes['aria-label'] = $item['aria_label'];
    }

    $triggers_markup .= sprintf(
        '<span data-slot="tabs-trigger-item"><input%1$s><label%2$s>%3$s</label></span>',
        hengegroup_theme_render_attributes($input_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        hengegroup_theme_render_attributes($label_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );

    $panel_attributes = [
        'data-slot' => 'tabs-content',
        'id' => $content_id,
        'data-value' => $item['value'],
        'aria-labelledby' => $trigger_id,
    ];

    $panels_markup .= sprintf(
        '<div%1$s>%2$s</div>',
        hengegroup_theme_render_attributes($panel_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $item['content'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$list_attributes = [
    'role' => 'radiogroup',
    'class' => $list_classes,
    'data-slot' => 'tabs-list',
    'data-orientation' => $orientation,
    'aria-orientation' => $orientation,
    'data-variant' => $variant,
];

if ($group_disabled) {
    $list_attributes['data-disabled'] = 'true';
}

if ($aria_label !== '') {
    $list_attributes['aria-label'] = $aria_label;
}

$wrapper_attributes = $attributes;
$wrapper_attributes['class'] = trim($root_classes . ($class_name !== '' ? ' ' . $class_name : ''));
$wrapper_attributes['data-slot'] = 'tabs';
$wrapper_attributes['data-orientation'] = $orientation;

$wrapper_attributes = hengegroup_theme_merge_data_attributes($wrapper_attributes, $data_attributes);

$panels_attributes = ['data-slot' => 'tabs-panels'];

if ($panels_classes !== '') {
    $panels_attributes['class'] = $panels_classes;
}

printf(
    '<div%1$s><div%2$s>%3$s</div><div%4$s>%5$s</div></div>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($list_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $triggers_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    hengegroup_theme_render_attributes($panels_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $panels_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
