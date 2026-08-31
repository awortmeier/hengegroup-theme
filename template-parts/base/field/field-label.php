<?php

declare(strict_types=1);

// shadcn/ui's FieldLabel: a native <label>, same underlying element and for/id pairing as
// template-parts/base/label.php. Rather than duplicating label.php's attribute-building logic,
// this file nests it with `data_slot: 'field-label'`, the same escape-hatch pattern
// input.php/textarea.php/scroll-area.php already use for their own composing parents -- label.php
// itself branches on that value to swap in FieldLabel's own, visually distinct class set (Phase 2,
// see that file's header comment) instead of Label's plain one, e.g. the boxed/highlighted look a
// FieldLabel gets when it wraps a nested field.php control (a "choice card"-style radio/checkbox
// option).
//
// Supported config: identical to template-parts/base/label.php's own (`text`/`label`, `for`,
// `class`/`attributes`/`data_attributes`) -- forwarded as-is, see that file's docs.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$config['data_slot'] = 'field-label';

get_template_part('template-parts/base/label', null, ['config' => $config]);
