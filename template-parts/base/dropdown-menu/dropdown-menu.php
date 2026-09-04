<?php

declare(strict_types=1);

// shadcn/ui's DropdownMenu wraps a headless UI primitive (historically Radix UI; shadcn now also
// ships Base UI/React Aria variants of many components): a JS-driven floating panel of menu items,
// portal-rendered, with roving-tabindex arrow-key navigation, type-ahead and outside-click/Escape
// dismissal (role="menu"/"menuitem" WAI-ARIA Menu pattern).
//
// Zero-JS baseline: native <details>/<summary> (the same technique as accordion.php) gives a real,
// working disclosure -- click (or Enter/Space on) the trigger to show/hide the panel, every item
// inside stays normally Tab-reachable, zero JS required (see CLAUDE.md #1). Two things a plain
// <details> does NOT give for free, both real, honestly-documented gaps in the zero-JS path (not
// silently pretended away): it doesn't auto-close on an outside click or Escape, and its content
// sits in normal document flow, not floating -- the latter is still solvable with pure CSS, not
// JS (`[data-slot="dropdown-menu"] { position: relative; }` +
// `[data-slot="dropdown-menu-content"] { position: absolute; }`, project-CSS concern, see
// CLAUDE.md #1). assets/js/template-parts/base/dropdown-menu.js enhances this into the full
// WAI-ARIA Menu pattern (roving tabindex, arrow-key/Home/End/type-ahead navigation,
// outside-click/Escape close) on top, same `data-js` progressive-enhancement gate as
// select.php/tooltip.php/combobox.php.
//
// Unlike accordion.php (where no extra role/aria-expanded is added on top of <details>/<summary>,
// since that content is just a generic panel, not a distinct ARIA widget), the content here
// legitimately gets `role="menu"` -- <details> only supplies disclosure semantics, nothing about
// "menu", so adding it isn't redundant/conflicting the way it would be on accordion.php's plain
// content panel. `aria-haspopup="menu"` on the trigger is likewise added as a static hint (unlike
// `aria-expanded`, which browsers already compute correctly from <summary>'s parent <details>
// `[open]` state, so it's deliberately NOT duplicated here).
//
// Composition: `trigger`/`content` are caller-provided, pre-rendered HTML (same
// convention as tooltip.php's `trigger`/aspect-ratio.php's `content`), built from
// dropdown-menu-item.php/-checkbox-item.php/-radio-group.php/-label.php/-group.php calls plus
// template-parts/base/separator/separator.php reused UNCHANGED for dividers (exactly like
// button-group.php reuses separator.php) and template-parts/base/kbd/kbd.php/kbd-group.php for
// shortcut hints (shadcn's own DropdownMenuShortcut is just styled text -- kbd.php already covers
// that, no new atom needed). IMPORTANT: `trigger` must NOT itself be (or contain) a focusable
// element such as a full button.php call -- <summary> is already the one interactive control here;
// nest only its inner content (e.g. escaped text + a buffered icon.php call), the same way
// button.php composes icon + text without an extra nested interactive wrapper.
//
// Deliberately out of scope for v1, a genuinely different/more complex component, not a variant of
// this one (same reasoning as native-select.php's `multiple`, combobox.php's chips mode):
// DropdownMenuSub (nested submenus) -- deferred, not silently dropped.
//
// Supported config:
//   trigger       string   required. Pre-rendered HTML for the <summary> trigger's inner content
//                          (see the composition note above)
//   content       string   required. Pre-rendered HTML for the menu panel (item components, see
//                          above)
//   side          string   top | right | bottom | left (default: bottom) -- sets data-side only,
//                          actual floating placement is project-CSS (see CLAUDE.md #1)
//   align         string   start (default) | center | end -- sets data-align only, same as `side`
//   id            string   native `id` on the outer <details>; auto-generated via wp_unique_id()
//                          when omitted
//   class / attributes / data_attributes   passthrough onto the outer
//                          <details data-slot="dropdown-menu"> wrapper

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$trigger = (string) ($config['trigger'] ?? '');
$content = (string) ($config['content'] ?? '');
$side = trim((string) ($config['side'] ?? 'bottom'));
$align = trim((string) ($config['align'] ?? 'start'));
$id = trim((string) ($config['id'] ?? ''));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($trigger) === '' || trim($content) === '') {
    return;
}

$allowed_sides = ['top', 'right', 'bottom', 'left'];
$allowed_aligns = ['start', 'center', 'end'];

if (!in_array($side, $allowed_sides, true)) {
    $side = 'bottom';
}

if (!in_array($align, $allowed_aligns, true)) {
    $align = 'start';
}

if ($id === '') {
    $id = 'hengegroup-theme-dropdown-menu-' . wp_unique_id();
}

$wrapper_attributes = $attributes;

if ($class_name !== '') {
    $wrapper_attributes['class'] = $class_name;
}

$wrapper_attributes['data-slot'] = 'dropdown-menu';
$wrapper_attributes['id'] = $id;

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $wrapper_attributes['data-' . $data_name] = $attribute_value;
}

$trigger_markup = sprintf(
    '<summary data-slot="dropdown-menu-trigger" aria-haspopup="menu">%s</summary>',
    $trigger, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

$content_markup = sprintf(
    '<div data-slot="dropdown-menu-content" role="menu" data-side="%1$s" data-align="%2$s">%3$s</div>',
    esc_attr($side),
    esc_attr($align),
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

printf(
    '<details%1$s>%2$s%3$s</details>',
    hengegroup_theme_render_attributes($wrapper_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $trigger_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
