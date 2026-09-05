<?php

declare(strict_types=1);

// Shared render helpers for template-parts/base/*. Before adding a new closure/helper inside a
// base component, check here first — if something similar already exists, reuse or extend it
// instead of duplicating it locally. New cross-component helpers belong in this file, not copied
// into each template part.

/**
 * Renders an associative array of HTML attributes into a string, e.g.
 * ['class' => 'foo', 'disabled' => true, 'data-x' => 'y'] -> ' class="foo" disabled data-x="y"'.
 *
 * - null/false values are omitted entirely.
 * - true renders the attribute name only (boolean HTML attributes).
 * - everything else is cast to string and escaped via esc_attr().
 */
function hengegroup_theme_render_attributes(array $attributes): string
{
    $parts = [];

    foreach ($attributes as $name => $value) {
        $attribute_name = trim((string) $name);

        if ($attribute_name === '' || $value === null || $value === false) {
            continue;
        }

        if ($value === true) {
            $parts[] = $attribute_name;
            continue;
        }

        $parts[] = sprintf('%s="%s"', $attribute_name, esc_attr((string) $value));
    }

    return $parts === [] ? '' : ' ' . implode(' ', $parts);
}

/**
 * Escapes $content and wraps every occurrence of the words in $highlighted_words in
 * <span class="font-accent">...</span>. Used by base components that support an
 * `accent_words`-config key (e.g. headline, text) to highlight individual words within a string.
 * Matches are whole occurrences of the given words, longest word first, so overlapping needles
 * don't get partially matched.
 */
function hengegroup_theme_render_accent_text(string $content, array $highlighted_words): string
{
    $needles = [];

    foreach ($highlighted_words as $word) {
        $word = trim((string) $word);

        if ($word === '') {
            continue;
        }

        $needles[$word] = true;
    }

    if ($needles === []) {
        return esc_html($content);
    }

    $needle_values = array_keys($needles);

    usort(
        $needle_values,
        static fn(string $left, string $right): int => strlen($right) <=> strlen($left),
    );

    $pattern =
        '/(' .
        implode(
            '|',
            array_map(static fn(string $word): string => preg_quote($word, '/'), $needle_values),
        ) .
        ')/';
    $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

    if (!is_array($parts)) {
        return esc_html($content);
    }

    $output = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        if (isset($needles[$part])) {
            $output .= '<span class="font-accent">' . esc_html($part) . '</span>';
            continue;
        }

        $output .= esc_html($part);
    }

    return $output;
}

/**
 * Renders a nested template-parts/base/icon.php call and returns its output as a string, for
 * components that embed an icon inline (e.g. a button's icon slot, an accordion trigger's
 * chevron, a badge's icon). Buffers get_template_part() output instead of every component
 * duplicating the same ob_start()/get_template_part()/ob_get_clean() closure locally.
 */
function hengegroup_theme_render_icon(array $icon_config): string
{
    ob_start();
    get_template_part('template-parts/base/icon', null, ['config' => $icon_config]);

    return (string) ob_get_clean();
}

/**
 * Renders a nested template-parts/base/image.php call and returns its output as a string, for
 * components that need to know whether an image actually resolved (image.php renders nothing for
 * a missing/invalid file -- name/set is checked via is_file(), see that file's own header comment)
 * before deciding what else to render, e.g. avatar.php falling back to initials/an icon, card.php
 * skipping its optional cover-media wrapper entirely. Buffers get_template_part() output instead
 * of every component duplicating the same ob_start()/get_template_part()/ob_get_clean() closure
 * locally -- same idiom as hengegroup_theme_render_icon() above, just for image.php.
 */
function hengegroup_theme_render_image(array $image_config): string
{
    ob_start();
    get_template_part('template-parts/base/image', null, ['config' => $image_config]);

    return (string) ob_get_clean();
}

/**
 * Deterministically derives the id template-parts/base/field/field-description.php renders for a
 * given control id (e.g. 'email' -> 'email-description'). A pure function of $control_id, not a
 * fresh wp_unique_id() -- both field-description.php (via its `for` config) and the control's own
 * `aria-describedby` can call this independently and always land on the identical string, instead
 * of the caller inventing/copying one id by hand in two places (see field.php's
 * header comment for the full wiring example).
 */
function hengegroup_theme_field_description_id(string $control_id): string
{
    return $control_id . '-description';
}

/**
 * Same idea as hengegroup_theme_field_description_id(), for template-parts/base/field/field-error.php.
 */
function hengegroup_theme_field_error_id(string $control_id): string
{
    return $control_id . '-error';
}

/**
 * Builds a ready-to-use `aria-describedby` value for a field's control, e.g.
 * hengegroup_theme_field_describedby('email') -> 'email-description email-error' (space-separated, as
 * aria-describedby requires for multiple ids). Pass $has_description/$has_error as false to omit
 * the id for whichever of field-description.php/field-error.php isn't actually rendered for this
 * control -- an id pointing at a non-existent element is worse than no id. Drop the result straight
 * into the control's own `attributes: ['aria-describedby' => ...]`.
 */
function hengegroup_theme_field_describedby(
    string $control_id,
    bool $has_description = true,
    bool $has_error = true,
): string {
    $ids = [];

    if ($has_description) {
        $ids[] = hengegroup_theme_field_description_id($control_id);
    }

    if ($has_error) {
        $ids[] = hengegroup_theme_field_error_id($control_id);
    }

    return implode(' ', $ids);
}

/**
 * Dev-time-only hint for icon-only interactive components (e.g. button.php, toggle.php) that
 * document `aria_label` as required but -- per this codebase's "never fatal" guard-clause
 * convention -- still render without it. `_doing_it_wrong()` already no-ops unless WP_DEBUG is on,
 * same as WordPress core's own API misuse warnings, so this is safe to call unconditionally on
 * every render: zero behavioural/markup difference in production, a visible nudge while developing.
 */
function hengegroup_theme_warn_missing_aria_label(
    string $component,
    bool $is_icon_only,
    string $aria_label,
): void {
    if (!$is_icon_only || $aria_label !== '') {
        return;
    }

    _doing_it_wrong(
        esc_html($component),
        esc_html(
            sprintf(
                'Icon-only %s is missing a non-empty `aria_label` config value -- screen readers ' .
                    'have no accessible name for it.',
                $component,
            ),
        ),
        'hengegroup-theme 1.0.0',
    );
}

/**
 * Computes the static Tailwind position classes for a panel floating next to a trigger element on
 * one of four sides, with an optional cross-axis alignment -- e.g. `side: 'bottom', align: 'start'`
 * -> 'top-[calc(100%+10px)] left-0'. Extracted from popover.php's own Phase 2 styling (see
 * docs/entscheidungen.md's popover.php entry) once tooltip.php turned out to need the exact same
 * side/align -> class lookup for its own resting/pre-JS position -- shared here instead of
 * duplicated per component, same "cross-cutting logic belongs in this file" convention as
 * hengegroup_theme_render_attributes() above.
 *
 * $side/$align are expected to already be validated/defaulted by the caller (same "trust the
 * caller" contract as hengegroup_theme_render_attributes()) -- an unrecognised value here quietly
 * falls back to the same bottom/center default every caller already uses for an invalid `side`/
 * `align` config value, it does not throw.
 *
 * Only covers the CONTENT box's own position -- NOT a floating panel's arrow, which (where one
 * exists, e.g. popover.php's/tooltip.php's own `*-arrow` element) has its own, per-component logic:
 * an arrow's classes differ in kind, not just value (border sides, a `-mt-1`-style overlap margin,
 * ...), and tooltip.php's arrow additionally has to stay reactive to `data-side` changing at
 * runtime (tooltip.js flips `side` after the initial render on a viewport collision -- see that
 * file's own header comment) in a way this content-box helper deliberately does not: the CONTENT
 * box's own position becomes irrelevant the moment tooltip.js's positionFloatingElement() takes
 * over (it always overrides via inline styles, whichever specific classes were rendered here), so
 * there is exactly one side/align combination worth emitting for it -- the configured one -- same
 * reasoning popover.php's own header comment already spelled out for why IT never needed the full
 * `group-data-[side=...]` combinatorial matrix in the first place, popover.php just has no JS flip
 * at all to begin with.
 */
function hengegroup_theme_floating_position_classes(
    string $side,
    string $align = 'center',
    int $gap_px = 10,
): string {
    if (!in_array($side, ['top', 'right', 'bottom', 'left'], true)) {
        $side = 'bottom';
    }

    $primary_axis_class = match ($side) {
        'top' => "bottom-[calc(100%+{$gap_px}px)]",
        'left' => "right-[calc(100%+{$gap_px}px)]",
        'right' => "left-[calc(100%+{$gap_px}px)]",
        default => "top-[calc(100%+{$gap_px}px)]", // 'bottom'
    };

    $cross_axis_class = in_array($side, ['top', 'bottom'], true)
        ? match ($align) {
            'start' => 'left-0',
            'end' => 'right-0',
            default => 'left-1/2 -translate-x-1/2', // 'center'
        }
        : match ($align) {
            'start' => 'top-0',
            'end' => 'bottom-0',
            default => 'top-1/2 -translate-y-1/2', // 'center'
        };

    return $primary_axis_class . ' ' . $cross_axis_class;
}
