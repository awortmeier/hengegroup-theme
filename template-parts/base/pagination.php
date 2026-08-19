<?php

declare(strict_types=1);

// shadcn/ui's Pagination is a small family of subcomponents (Pagination, PaginationContent,
// PaginationItem, PaginationLink, PaginationPrevious, PaginationNext, PaginationEllipsis) that a
// caller composes by hand, one <PaginationItem> per page/control. This file collapses that family
// into a single config-driven `items` array instead -- same shape as breadcrumb.php's own
// items-array API: nothing in the family is meaningfully reused standalone
// outside a pagination list the way table/*'s row/cell atoms are, so a one-file-per-subcomponent
// split (like table/, dropdown-menu/) would just be indirection a caller has to assemble by hand
// for no benefit.
//
// Nests template-parts/base/button.php for every interactive item -- shadcn's own
// PaginationLink literally renders buttonVariants({ variant: isActive ? "outline" : "ghost", size
// }) on an <a>, so button.php's href-renders-as-<a> mode (its asChild/Slot analog) is a 1:1 match,
// including its `disabled` handling (drops href, sets aria-disabled) for boundary controls -- same
// idiom as data-table.php's own (structurally different, page-count-driven rather than
// items-array-driven) pagination footer. `previous`/`next` items reuse button.php's icon+text
// slot (`icon`/`icon_position`) for the chevron instead of hand-rolling icon-plus-label markup.
// Deliberately does NOT try to rename the nested button's `data-slot="button"` to
// `"pagination-link"` the way shadcn's own DOM does -- this codebase protects `data-slot` as a
// structural attribute nested components own, and carousel-previous.php/carousel-next.php already
// established the precedent for this exact situation: button.php stays the single source of truth
// for what "is a button" looks like ([data-slot="button"][data-variant="..."][data-size="..."]
// inside <li data-slot="pagination-item"> is hook enough for Phase 2 styling), any
// component-specific meaning is layered on top via other attributes instead of a renamed
// data-slot. The ellipsis item nests template-parts/base/icon.php via hengegroup_theme_render_icon(),
// same default Lucide `ellipsis` glyph as breadcrumb.php's own ellipsis item.
//
// Supported config:
//   items       array   required, ordered list of item configs. `type`:
//     'page'        (default) a page-number control. Keys:
//       text          string   required, visible label (e.g. "1", "2") -- also the link's
//                                accessible name, matching shadcn (no separate aria-label prop on
//                                PaginationLink)
//       href          string   renders via button.php's href mode (<a>); omitting it falls back
//                                to button.php's own <button> rendering -- the common
//                                real-navigation case always supplies it
//       is_active     bool     default false. Current-page indicator (shadcn's `isActive`) --
//                                sets aria-current="page" + data-active="true" and switches the
//                                nested button.php's variant to 'outline' (else 'ghost'), matching
//                                shadcn's PaginationLink exactly
//       disabled      bool     default false. Passed to the nested button.php (drops href, sets
//                                aria-disabled)
//       size          string   button.php size override (default 'icon', matching shadcn's own
//                                PaginationLink default)
//     'previous'    shadcn's PaginationPrevious. Keys:
//       href          string   see 'page' above
//       text          string   visible label (default: translated "Previous")
//       aria_label    string   default: translated "Go to previous page" (matches shadcn's own
//                                hardcoded default). Applied as a raw <a>/<button> aria-label
//                                attribute rather than button.php's own `aria_label` config (which
//                                only renders for icon-only buttons -- this item always has visible
//                                text alongside its icon, same as shadcn)
//       icon          array    icon.php config override (default: ['name' => 'chevron-left', 'set'
//                                => 'lucide']), rendered before the text
//       disabled      bool     default false
//       size          string   button.php size override (default 'default', matching shadcn)
//     'next'        mirrors 'previous': default text "Next", default aria_label "Go to next page",
//                    default icon ['name' => 'chevron-right', 'set' => 'lucide'] rendered after
//                    the text (shadcn puts the chevron on the trailing side for Next)
//     'ellipsis'    shadcn's PaginationEllipsis -- non-interactive "more pages" placeholder,
//                    aria-hidden, with a sr-only "More pages" label mirrored 1:1 from shadcn (a
//                    functional Tailwind utility class, not an optical one -- allowed in Phase 1,
//                    same precedent as dialog.php's `title_visually_hidden`). Keys:
//       icon          array    icon.php config override (default: ['name' => 'ellipsis', 'set' =>
//                                'lucide'])
//     class           string   passthrough onto this item's <li data-slot="pagination-item">
//   aria_label  string   accessible name for the outer <nav> (default: translated 'pagination',
//                        matching shadcn's own hardcoded default) -- same override precedent as
//                        breadcrumb.php's own aria_label
//   class / attributes / data_attributes   passthrough onto the outer <nav data-slot="pagination">

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$items_config = is_array($config['items'] ?? null) ? $config['items'] : [];
$aria_label = trim((string) ($config['aria_label'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if ($items_config === []) {
    return;
}

if ($aria_label === '') {
    $aria_label = __('pagination', 'hengegroup-theme');
}

$allowed_types = ['page', 'previous', 'next', 'ellipsis'];

$render_button = static function (array $button_config): string {
    ob_start();
    get_template_part('template-parts/base/button', null, ['config' => $button_config]);

    return (string) ob_get_clean();
};

$list_items_markup = '';

foreach ($items_config as $item_config) {
    if (!is_array($item_config)) {
        continue;
    }

    $type = trim((string) ($item_config['type'] ?? 'page'));

    if (!in_array($type, $allowed_types, true)) {
        $type = 'page';
    }

    $item_class = trim((string) ($item_config['class'] ?? ''));
    $item_attributes = ['data-slot' => 'pagination-item'];

    if ($item_class !== '') {
        $item_attributes['class'] = $item_class;
    }

    if ($type === 'ellipsis') {
        $icon_config = is_array($item_config['icon'] ?? null)
            ? $item_config['icon']
            : ['name' => 'ellipsis', 'set' => 'lucide'];

        $inner_markup = sprintf(
            '<span data-slot="pagination-ellipsis" aria-hidden="true">%1$s<span class="sr-only">%2$s</span></span>',
            hengegroup_theme_render_icon($icon_config), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            esc_html__('More pages', 'hengegroup-theme'),
        );
    } elseif ($type === 'previous' || $type === 'next') {
        $text = trim((string) ($item_config['text'] ?? ''));
        $icon_config = is_array($item_config['icon'] ?? null) ? $item_config['icon'] : null;
        $link_aria_label = trim((string) ($item_config['aria_label'] ?? ''));
        $size = trim((string) ($item_config['size'] ?? ''));

        if ($type === 'previous') {
            $icon_position = 'start';
            $default_text = __('Previous', 'hengegroup-theme');
            $default_icon = ['name' => 'chevron-left', 'set' => 'lucide'];
            $default_aria_label = __('Go to previous page', 'hengegroup-theme');
        } else {
            $icon_position = 'end';
            $default_text = __('Next', 'hengegroup-theme');
            $default_icon = ['name' => 'chevron-right', 'set' => 'lucide'];
            $default_aria_label = __('Go to next page', 'hengegroup-theme');
        }

        $inner_markup = $render_button([
            'href' => trim((string) ($item_config['href'] ?? '')),
            'variant' => 'ghost',
            'size' => $size !== '' ? $size : 'default',
            'text' => $text !== '' ? $text : $default_text,
            'icon' => $icon_config ?? $default_icon,
            'icon_position' => $icon_position,
            'disabled' => !empty($item_config['disabled']),
            'attributes' => [
                'aria-label' => $link_aria_label !== '' ? $link_aria_label : $default_aria_label,
            ],
        ]);
    } else {
        $text = trim((string) ($item_config['text'] ?? ''));

        if ($text === '') {
            continue;
        }

        $is_active = !empty($item_config['is_active']);
        $size = trim((string) ($item_config['size'] ?? ''));
        $link_attributes = [];

        if ($is_active) {
            $link_attributes['aria-current'] = 'page';
            $link_attributes['data-active'] = 'true';
        }

        $inner_markup = $render_button([
            'href' => trim((string) ($item_config['href'] ?? '')),
            'variant' => $is_active ? 'outline' : 'ghost',
            'size' => $size !== '' ? $size : 'icon',
            'text' => $text,
            'disabled' => !empty($item_config['disabled']),
            'attributes' => $link_attributes,
        ]);
    }

    $list_items_markup .= sprintf(
        '<li%1$s>%2$s</li>',
        hengegroup_theme_render_attributes($item_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $inner_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

if ($list_items_markup === '') {
    return;
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'pagination';
$wrapper_attributes['aria-label'] = $aria_label;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<nav%1$s><ul data-slot="pagination-content">%2$s</ul></nav>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $list_items_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
