<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's KbdGroup: a plain, content-agnostic wrapper for grouping
// several template-parts/base/kbd/kbd.php calls into one shortcut display (e.g. "Ctrl" + "K").
// Same nesting pattern as aspect-ratio.php: buffer the inner kbd.php output(s) and pass the
// combined HTML string as `content`.
//
// Phase 2 (CLAUDE.md Regel 1): shadcn's own KbdGroup (registry/new-york-v4/ui/kbd.tsx,
// live-checked 2026-09-03) is just `inline-flex items-center gap-1` -- no background of its own,
// individual Kbd children carry the whole visual weight. The Claude-Design reference "Hengegroup"
// (see kbd.php's own header for that workflow) adds a "Gruppe" look on top for compact, no-
// separator key blocks: a padded, rounded neutral-grey pill the keys sit inside of. Kept as a
// deliberate deviation the same way kbd.php's own header documents its deviations -- `gap-1`
// widened slightly to `gap-0.5` (2px, matches the reference's own key-to-key gap) and a
// background/padding/radius added: `bg-neutral-200` (this project's established "use Tailwind's
// own neutral scale for brand grey" convention, see assets/css/tokens.css's file header --
// approximates the reference's #e5e3df/grey-medium the same way tokens.css already documents
// elsewhere), `p-0.75` (3px, exact match), `rounded-lg` (8px, nearest real step to the
// reference's 9px, same "no arbitrary px" rule as button.php/typography.php, and matches
// kbd.php's own `lg`-size radius for visual consistency between the two files).
//
//   ob_start();
//   get_template_part('template-parts/base/kbd/kbd', null, ['config' => ['text' => 'Ctrl']]);
//   get_template_part('template-parts/base/kbd/kbd', null, ['config' => ['text' => 'K']]);
//   $keys_markup = (string) ob_get_clean();
//
//   get_template_part('template-parts/base/kbd/kbd-group', null, [
//       'config' => ['content' => $keys_markup],
//   ]);
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (caller's responsibility to
//                       escape/build, e.g. via template-parts/base/kbd/kbd.php)
//   class     string   appended AFTER the computed base classes (plain string concat, same
//                       caveat as button.php's/badge.php's own `class` -- fine for additive
//                       classes, not a reliable bg-*/p-*/rounded-* override)
//   attributes / data_attributes   passthrough, as in the other base parts

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$content = (string) ($config['content'] ?? '');
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

if (trim($content) === '') {
    return;
}

$base_classes = 'inline-flex items-center gap-0.5 rounded-lg bg-neutral-200 p-0.75';

$element_attributes = $attributes;
$element_attributes['class'] = trim($base_classes . ($class_name !== '' ? ' ' . $class_name : ''));

$element_attributes['data-slot'] = 'kbd-group';

foreach ($data_attributes as $attribute_key => $attribute_value) {
    $data_name = trim((string) $attribute_key);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $attribute_value;
}

printf(
    '<div%1$s>%2$s</div>',
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
