<?php

declare(strict_types=1);

// Direct translation of shadcn/ui's KbdGroup: a plain, content-agnostic wrapper for grouping
// several template-parts/base/kbd/kbd.php calls into one shortcut display (e.g. "Ctrl" + "K").
// Same nesting pattern as aspect-ratio.php: buffer the inner kbd.php output(s) and pass the
// combined HTML string as `content`.
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
//   class / attributes / data_attributes   passthrough, as in the other base parts

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

$element_attributes = $attributes;

if ($class_name !== '') {
    $element_attributes['class'] = $class_name;
}

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
