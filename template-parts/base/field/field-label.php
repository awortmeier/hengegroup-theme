<?php

declare(strict_types=1);

// <label> for a field: label.php with data_slot "field-label". Config as label.php.

if (!isset($args['config']) || !is_array($args['config'])) {
    return;
}

$config = $args['config'];
$config['data_slot'] = 'field-label';

get_template_part('template-parts/base/label', null, ['config' => $config]);
