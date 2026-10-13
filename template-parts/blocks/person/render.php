<?php

declare(strict_types=1);

// Karte im Block "Personen" (<li>). Das Porträt ist dekorativ, der Name steht direkt darunter. Ohne
// Name keine Ausgabe.

if (!is_array($attributes ?? null)) {
    return;
}

$name = trim((string) ($attributes['name'] ?? ''));

if ($name === '') {
    return;
}

$image_id = (int) ($attributes['imageId'] ?? 0);

get_template_part('template-parts/base/card', null, [
    'config' => [
        'tag' => 'li',
        'elevation' => 'raised',
        'size' => 'lg',
        'image' =>
            $image_id > 0
                ? [
                    'attachment_id' => $image_id,
                    'size' => 'large',
                    'decorative' => true,
                    'loading' => 'lazy',
                    'class' => 'aspect-4/3 w-full object-cover',
                ]
                : null,
        'title' => $name,
        'title_variant' => 'body-base',
        'description' => trim((string) ($attributes['role'] ?? '')),
    ],
]);
