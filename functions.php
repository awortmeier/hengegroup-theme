<?php

declare(strict_types=1);

// enshrined/svg-sanitize (siehe inc/setup/theme-svg-support.php) ist die erste echte Laufzeit-
// Composer-Abhaengigkeit dieses Themes (composer.json's require, nicht require-dev) -- guarded
// per file_exists(), falls jemand klont/deployed, ohne vorher `composer install` laufen zu lassen
// (theme-svg-support.php faellt in diesem Fall selbst fail-closed auf "kein SVG-Upload" zurueck).
if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
    require_once get_template_directory() . '/vendor/autoload.php';
}

require_once get_template_directory() . '/inc/template-parts/helpers.php';
require_once get_template_directory() . '/inc/template-parts/navigation.php';
require_once get_template_directory() . '/inc/template-parts/woocommerce-product-card.php';

require_once get_template_directory() . '/inc/setup/theme-setup.php';
require_once get_template_directory() . '/inc/setup/theme-assets.php';
require_once get_template_directory() . '/inc/setup/theme-blocks.php';
require_once get_template_directory() . '/inc/setup/theme-svg-support.php';
require_once get_template_directory() . '/inc/setup/theme-admin.php';
require_once get_template_directory() . '/inc/setup/theme-admin-woocommerce.php';
require_once get_template_directory() . '/inc/setup/theme-woocommerce-products.php';
require_once get_template_directory() . '/inc/setup/theme-careers.php';
require_once get_template_directory() . '/inc/setup/theme-seo-admin.php';
require_once get_template_directory() . '/inc/setup/theme-seo-output.php';
require_once get_template_directory() . '/inc/setup/theme-hardening.php';
require_once get_template_directory() . '/inc/setup/theme-hardening-woocommerce.php';
