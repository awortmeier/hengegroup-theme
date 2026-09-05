<?php

declare(strict_types=1);

// API modeled on shadcn/ui's Card family (Card / CardHeader / CardTitle / CardDescription /
// CardAction / CardContent / CardFooter) -- no headless UI primitive underneath at all, just plain
// <div> wrappers with no interactive state, so unlike e.g. accordion.php/select.php there is no
// native-HTML-vs-JS deliberation to make here (CLAUDE.md #1). shadcn's six sub-components
// collapse into ONE config-driven file here instead of a six-file subfolder (the "more
// than one file -> own subfolder" rule doesn't apply) -- same spirit as badge.php/accordion.php
// folding their own sub-parts into config keys/arrays instead of separate files: title/
// description/image are genuinely single-purpose slots this component renders itself by nesting
// other base components, while action/content/footer stay content-agnostic
// pre-rendered-HTML slots (same convention as aspect-ratio.php's `content`) because shadcn's own
// CardAction/CardContent/CardFooter accept arbitrary children, not just text.
//
// Nests typography.php for the title (variant 'body-lg') and description (variant 'body-sm') slots
// via its `data_slot` escape hatch (same trick as input.php's/textarea.php's/label.php's own
// `data_slot`, see those files' own header comments) so the rendered elements carry
// data-slot="card-title"/"card-description" instead of typography.php's own default
// data-slot="typography" -- lets project CSS target [data-slot="card-title"] directly without an
// extra wrapper element. Nests image.php for the optional cover-media slot the same way avatar.php
// nests image.php: buffer the output and check for emptiness (image.php renders nothing for a
// missing/invalid file), skip the media wrapper entirely rather than emitting an empty <div>.
//
// Phase 2 (CLAUDE.md Regel 1): styled via Tailwind on the strength of the Claude-Design reference
// "Hengegroup" (https://claude.ai/code/artifact/c2fdca5b-79fc-47b7-92c8-e861966ac106, same
// `.dc.html` reference workflow as popover.php's/toast.php's own entries, see
// docs/entscheidungen.md for this component's entry). Card/CardHeader/CardTitle/CardDescription/
// CardAction/CardContent/CardFooter classes are shadcn's own current ones (live-checked against
// shadcn-ui/ui's `apps/v4/registry/new-york-v4/ui/card.tsx` on 2026-09-05), applied to this file's
// single-file structure instead of six separate components, with these deliberate deviations:
//   - radius bumped from shadcn's stock `rounded-xl` to `rounded-2xl` -- this project's own
//     established radius for card/floating surfaces (popover.php's/toast.php's/calendar.php's own
//     `rounded-2xl` cards), not the reference's literal 20px either, same "standardize across
//     surfaces instead of over-fitting to one demo number" precedent as popover.php's own entry.
//   - `border` alone (shadcn relies on the browser default border-color) swapped for explicit
//     `border-border` -- this project has a purpose-built `--color-border`/`--color-card`/
//     `--color-card-foreground` token trio already named for exactly this role (tokens.css), same
//     "prefer an existing semantic token over an implicit default" precedent as popover.php's own
//     `--color-popover` reuse.
//   - `[.border-b]:pb-6` (shadcn's own optional CardHeader-bottom-divider affordance, activated by
//     a caller manually adding a `border-b` class) is dropped entirely -- nothing in this file lets
//     a caller reach that class on the header `<div>` (only the outer element takes a passthrough
//     `class`), so it would be dead, unreachable code; no reference example shows a header divider
//     either. `[.border-t]:pt-6` on the footer is KEPT because this file adds a real, reachable way
//     to trigger it -- see `footer_divider` below.
//   - `footer_divider` (new bool config, see below) -- PHP has no `cn()`/tailwind-merge to detect
//     "did the caller happen to add a `border-t` class to their own footer content", so shadcn's
//     own trick (inspect the CALLER's className for a `border-t` substring via `[.border-t]:pt-6`)
//     can't apply to a *wrapper* div the caller doesn't control here; exposed as an explicit
//     boolean instead, same idea (a visible top divider bumps the footer's own top padding to
//     `pt-6`/`pt-4`), reachable this time. The reference's "Musteranfrage" form card shows this
//     (divider above the submit button); its "Edelkorund" card shows the plain no-divider default.
//   - `gap-3`/`gap-2` (size-dependent, see below) added to the footer's own `flex items-center` --
//     shadcn's stock CardFooter has no gap of its own (real shadcn usage rarely concatenates more
//     than one bare child), but this file's `footer` config is raw pre-rendered HTML, often
//     multiple buffered button.php calls with nothing of their own to add spacing between them
//     (same reasoning as button-group.php's own `gap-2`).
//   - `href` (new config, see below) -- shadcn's Card has no polymorphic root, but this project
//     already has the exact "asChild/Slot analog via `href`" idiom on two sibling components
//     (button.php, badge.php); the reference's "Mit Bild" section needs the WHOLE card to be one
//     clickable target (an `<a>` wrapping image+title+description+link), so this file adopts the
//     same idiom rather than inventing a different one. Forces `tag` to `a` when given (same
//     precedence as badge.php's own `href`). The hover-lift itself
//     (`[a&]:hover:shadow-lg [a&]:hover:-translate-y-0.5`, matching the reference's own
//     `box-shadow`/`translateY(-2px)` hover) uses the same `[a&]:` shadcn idiom badge.php's own
//     `outline` variant already relies on -- inert unless the rendered root actually IS an `<a>`,
//     so it's safe to bake into the base classes unconditionally rather than branching on `$href`.
//   - `media_badge` (new config, see below) -- the reference's product cards overlay a colored
//     label (badge.php's own look) on the top-left corner of the cover image; `image` alone has no
//     room for a sibling element inside `card-media`, so this adds one narrowly-scoped slot for it
//     rather than growing `image` itself into something it isn't (image.php stays pure `<img>`
//     plumbing, see its own header comment).
//   - `size: "default" | "sm"` ("uses smaller spacing", per shadcn's own Card docs prose) now
//     actually styles both steps -- Phase 1 only wired the `data-size` hook. shadcn's own current
//     docs mention this prop and a `--card-spacing` CSS variable, but the live-checked source file
//     above shows neither (registry drift between docs prose and that exact file/version); rather
//     than guess at an unconfirmed CSS-variable mechanism, `sm` here is this file's own
//     straightforward reduced-spacing step (gap/padding one Tailwind stop down from `default`),
//     same spirit as the documented intent.
//   - the reference's "Auf dunklem Grund" section was NOT ported, same reason as every other
//     component's Phase-2 entry so far (popover.php/toast.php/tooltip.php/etc.) -- this theme has
//     no dark-mode/dark-surface strategy yet, see docs/to-do.md.
//   - No file-per-variant split, no `card/` folder move (the task explicitly asked to check, same
//     as popover.php's own entry). Every "variant" the reference shows -- the two-column "Basis"
//     cards (a data-list-with-footer-buttons card and a form card, both differing only in what
//     `content`/`footer` markup the caller supplies), the "Mit Bild" clickable product card
//     (`image` + `media_badge` + `href`), and the "Kompakt" stat cards (`content` only, no
//     title/description/footer) -- is markup/config the existing single-file component already
//     models via `title`/`description`/`image`/`media_badge`/`action`/`content`/`footer`/`href`,
//     not a structurally different composition.
//   - `page-component-showcase-card.php` new, analog to the other showcase pages.
//
// Supported config:
//   title          string   optional. Visible title, rendered via typography.php (variant
//                             'body-lg', data_slot 'card-title')
//   title_tag      string   h2 | h3 | h4 | h5 | h6 (default: h3) -- overrides the rendered heading
//                             tag while keeping the 'body-lg' visual variant, same
//                             config-vs-visual-style split as accordion.php's `heading_tag`
//   description    string   optional. Secondary text below the title, rendered via typography.php
//                             (variant 'body-sm', color 'neutral', data_slot 'card-description')
//   image          array    optional. image.php config for a cover-media slot, rendered above the
//                             header, wrapped in <div data-slot="card-media"> -- omitted entirely
//                             when image.php itself renders nothing (missing/invalid file); bleeds
//                             edge-to-edge (negative top margin + rounded top corners matching the
//                             card's own) when present
//   media_badge    string   optional. Pre-rendered HTML (e.g. a buffered badge.php call) shown as
//                             an absolutely-positioned overlay in the media slot's top-left corner
//                             -- ignored when `image` doesn't actually render anything
//   action         string   optional. Pre-rendered HTML for the header's top-right slot (shadcn's
//                             CardAction), e.g. a buffered button.php icon-only call -- caller's
//                             responsibility to build/escape (content-agnostic, same convention as
//                             aspect-ratio.php's `content`)
//   content        string   optional. Pre-rendered HTML for the card body (shadcn's CardContent) --
//                             arbitrary markup, not limited to text
//   footer         string   optional. Pre-rendered HTML for the footer (shadcn's CardFooter), e.g.
//                             buffered button.php calls (same convention as button-group.php's
//                             `content`)
//   footer_divider bool     default false -- adds a visible top border + extra top padding above
//                             the footer (shadcn's own optional CardFooter divider affordance, see
//                             the Phase 2 note above); ignored when `footer` is empty
//   href           string   optional. Renders the outer element as `<a href="...">` instead of
//                             `tag` (shadcn asChild/Slot analog, same idiom as button.php's/
//                             badge.php's own `href`) -- makes the WHOLE card one clickable target,
//                             with a hover-lift affordance (see the Phase 2 note above)
//   tag            string   div (default) | article | section -- semantic root element override,
//                             e.g. `article` for an independent card within a list/grid of cards;
//                             ignored when `href` is given (forced to `a` instead)
//   size           string   default | sm -- sets data-size on the outer element AND now drives
//                             real spacing (gap/padding one Tailwind stop down for `sm`, see the
//                             Phase 2 note above)
//   class / attributes / data_attributes   passthrough onto the outer <div data-slot="card">
//
// Header (title/description/action) is only rendered when at least one of the three is given --
// same conditional-region convention as shadcn's own CardHeader (its grid layout already changes
// based on whether a CardAction is present). Nothing is printed at all when the whole config
// resolves empty (no image, no header, no content, no footer).

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];

$title = trim((string) ($config['title'] ?? ''));
$title_tag = strtolower(trim((string) ($config['title_tag'] ?? 'h3')));
$description = trim((string) ($config['description'] ?? ''));
$image_config = is_array($config['image'] ?? null) ? $config['image'] : null;
$media_badge = (string) ($config['media_badge'] ?? '');
$action = (string) ($config['action'] ?? '');
$content = (string) ($config['content'] ?? '');
$footer = (string) ($config['footer'] ?? '');
$footer_divider = !empty($config['footer_divider']);
$href = trim((string) ($config['href'] ?? ''));
$tag = strtolower(trim((string) ($config['tag'] ?? 'div')));
$size = trim((string) ($config['size'] ?? 'default'));
$class_name = trim((string) ($config['class'] ?? ''));
$attributes = is_array($config['attributes'] ?? null) ? $config['attributes'] : [];
$data_attributes = is_array($config['data_attributes'] ?? null) ? $config['data_attributes'] : [];

$allowed_title_tags = ['h2', 'h3', 'h4', 'h5', 'h6'];

if (!in_array($title_tag, $allowed_title_tags, true)) {
    $title_tag = 'h3';
}

$allowed_tags = ['div', 'article', 'section'];

if (!in_array($tag, $allowed_tags, true)) {
    $tag = 'div';
}

if ($href !== '') {
    $tag = 'a';
}

$allowed_sizes = ['default', 'sm'];

if (!in_array($size, $allowed_sizes, true)) {
    $size = 'default';
}

// Size -> spacing map (Phase 2, see file header): `sm` steps every gap/padding down one real
// Tailwind stop from `default` instead of an arbitrary/guessed value.
$size_classes = [
    'default' => [
        'outer_gap' => 'gap-6',
        'outer_py' => 'py-6',
        'section_px' => 'px-6',
        'header_gap' => 'gap-2',
        'footer_gap' => 'gap-3',
        'media_mt' => '-mt-6',
        'footer_divider_pt' => 'pt-6',
    ],
    'sm' => [
        'outer_gap' => 'gap-4',
        'outer_py' => 'py-4',
        'section_px' => 'px-4',
        'header_gap' => 'gap-1.5',
        'footer_gap' => 'gap-2',
        'media_mt' => '-mt-4',
        'footer_divider_pt' => 'pt-4',
    ],
];
$s = $size_classes[$size];

$media_markup = '';

if ($image_config !== null) {
    $image_markup = hengegroup_theme_render_image($image_config);

    if (trim($image_markup) !== '') {
        $media_badge_markup =
            trim($media_badge) !== ''
                ? sprintf(
                    '<div class="absolute top-3 left-3" data-slot="card-media-badge">%s</div>',
                    $media_badge, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                )
                : '';

        $media_markup = sprintf(
            '<div class="relative %1$s overflow-hidden rounded-t-2xl" data-slot="card-media">%2$s%3$s</div>',
            $s['media_mt'],
            $image_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $media_badge_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }
}

$title_markup = '';

if ($title !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-lg',
            'tag' => $title_tag,
            'text' => $title,
            'data_slot' => 'card-title',
            // shadcn's own CardTitle is `leading-none font-semibold` -- body-lg is font-normal by
            // default (see typography.php), added here the same way leading-none is, since neither
            // is part of body-lg's own shared scale.
            'class' => 'leading-none font-semibold',
        ],
    ]);
    $title_markup = (string) ob_get_clean();
}

$description_markup = '';

if ($description !== '') {
    ob_start();
    get_template_part('template-parts/base/typography', null, [
        'config' => [
            'variant' => 'body-sm',
            'text' => $description,
            'data_slot' => 'card-description',
            // shadcn's own CardDescription is `text-muted-foreground` -- typography.php's `color`
            // axis already models this exact role (see its own header comment).
            'color' => 'neutral',
            'class' => 'text-pretty',
        ],
    ]);
    $description_markup = (string) ob_get_clean();
}

$action_markup =
    trim($action) !== ''
        ? sprintf(
            '<div class="col-start-2 row-span-2 row-start-1 self-start justify-self-end" data-slot="card-action">%s</div>',
            $action, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$header_markup = '';

if ($title_markup !== '' || $description_markup !== '' || $action_markup !== '') {
    $header_markup = sprintf(
        '<div class="@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start %1$s %2$s has-data-[slot=card-action]:grid-cols-[1fr_auto]" data-slot="card-header">%3$s%4$s%5$s</div>',
        $s['header_gap'],
        $s['section_px'],
        $title_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $description_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $action_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

$content_markup =
    trim($content) !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-content">%2$s</div>',
            $s['section_px'],
            $content, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$footer_class = "flex items-center {$s['footer_gap']} {$s['section_px']}";

if ($footer_divider) {
    $footer_class .= " border-t border-border {$s['footer_divider_pt']}";
}

$footer_markup =
    trim($footer) !== ''
        ? sprintf(
            '<div class="%1$s" data-slot="card-footer">%2$s</div>',
            trim($footer_class),
            $footer, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        )
        : '';

$inner_html = $media_markup . $header_markup . $content_markup . $footer_markup;

if (trim($inner_html) === '') {
    return;
}

$element_attributes = $attributes;

// Base classes are shadcn's own Card ones (`flex flex-col gap-6 rounded-xl border bg-card py-6
// text-card-foreground shadow-sm`, live-checked 2026-09-05) with this file's own radius/border-
// token/hover-lift deviations documented in the file header above.
$computed_class = trim(
    "bg-card text-card-foreground flex flex-col {$s['outer_gap']} rounded-2xl border border-border " .
        "{$s['outer_py']} shadow-sm [a&]:cursor-pointer [a&]:no-underline [a&]:transition-all " .
        '[a&]:hover:shadow-lg [a&]:hover:-translate-y-0.5',
);

$element_attributes['class'] = trim(
    $computed_class . ($class_name !== '' ? ' ' . $class_name : ''),
);

$element_attributes['data-slot'] = 'card';
$element_attributes['data-size'] = $size;

if ($href !== '') {
    $element_attributes['href'] = $href;
}

foreach ($data_attributes as $name => $value) {
    $data_name = trim((string) $name);

    if ($data_name === '') {
        continue;
    }

    $element_attributes['data-' . $data_name] = $value;
}

printf(
    '<%1$s%2$s>%3$s</%1$s>',
    esc_html($tag),
    hengegroup_theme_render_attributes($element_attributes), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    $inner_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
