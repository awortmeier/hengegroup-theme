<?php

declare(strict_types=1);

// shadcn/ui's AttachmentGroup: a horizontally scrollable, snapping row of attachment.php cards
// with an edge fade effect. Structurally that's exactly template-parts/base/scroll-area.php with
// `orientation: 'horizontal'` -- nested here via scroll-area.php's `data_slot`
// escape hatch (requesting `'attachment-group'` instead of the generic `'scroll-area'`) rather than
// reimplementing scrolling. Both the edge-fade effect and the scroll-snap behaviour (e.g.
// `scroll-snap-type: x mandatory` on this wrapper, `scroll-snap-align: start` on each nested
// attachment.php card) are project-CSS concerns, not baked in here (CLAUDE.md #1).
//
// Supported config:
//   content   string   required. Pre-rendered HTML to wrap (buffered attachment.php calls)
//   class / attributes / data_attributes   passthrough onto the outer
//                       <div data-slot="attachment-group"> wrapper (forwarded to scroll-area.php)

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

get_template_part('template-parts/base/scroll-area', null, [
    'config' => [
        'content' => $content,
        'orientation' => 'horizontal',
        'data_slot' => 'attachment-group',
        'class' => $class_name,
        'attributes' => $attributes,
        'data_attributes' => $data_attributes,
    ],
]);
