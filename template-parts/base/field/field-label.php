<?php

declare(strict_types=1);

// shadcn/ui's FieldLabel: a native <label>, same underlying element and for/id pairing as
// template-parts/base/label.php ("works with both direct inputs and nested Field children" is a
// styling nuance, not a different HTML element). Rather than duplicating label.php's
// attribute-building logic, this file nests it with `data_slot: 'field-label'`,
// the same escape-hatch pattern input.php/textarea.php/scroll-area.php already use for their own
// composing parents.
//
// Supported config: identical to template-parts/base/label.php's own (`text`/`label`, `for`,
// `class`/`attributes`/`data_attributes`) -- forwarded as-is, see that file's docs.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$config['data_slot'] = 'field-label';

get_template_part('template-parts/base/label', null, ['config' => $config]);
