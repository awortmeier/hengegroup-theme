<?php

declare(strict_types=1);

// Composer-Autoload (svg-sanitize) nur, wenn vendor/ existiert; theme-svg-support.php sperrt sonst
// SVG-Uploads.
if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
    require_once get_template_directory() . '/vendor/autoload.php';
}

require_once get_template_directory() . '/inc/template-parts/helpers.php';
require_once get_template_directory() . '/inc/template-parts/navigation.php';
require_once get_template_directory() . '/inc/template-parts/woocommerce-product-card.php';
require_once get_template_directory() . '/inc/template-parts/careers.php';
require_once get_template_directory() . '/inc/template-parts/products.php';

require_once get_template_directory() . '/inc/setup/theme-setup.php';
require_once get_template_directory() . '/inc/setup/theme-assets.php';
require_once get_template_directory() . '/inc/setup/theme-blocks.php';
require_once get_template_directory() . '/inc/setup/theme-svg-support.php';
require_once get_template_directory() . '/inc/setup/theme-admin.php';
require_once get_template_directory() . '/inc/setup/theme-admin-woocommerce.php';
require_once get_template_directory() . '/inc/setup/theme-woocommerce-products.php';
require_once get_template_directory() . '/inc/setup/theme-products.php';
require_once get_template_directory() . '/inc/setup/theme-products-admin.php';
require_once get_template_directory() . '/inc/setup/theme-requests.php';
require_once get_template_directory() . '/inc/setup/theme-product-inquiries.php';
require_once get_template_directory() . '/inc/setup/theme-careers.php';
require_once get_template_directory() . '/inc/setup/theme-careers-admin.php';
require_once get_template_directory() . '/inc/setup/theme-careers-seo.php';
require_once get_template_directory() . '/inc/setup/theme-careers-application.php';
require_once get_template_directory() . '/inc/setup/theme-seo-admin.php';
require_once get_template_directory() . '/inc/setup/theme-footer-admin.php';
require_once get_template_directory() . '/inc/setup/theme-seo-output.php';
require_once get_template_directory() . '/inc/setup/theme-hardening.php';
require_once get_template_directory() . '/inc/setup/theme-hardening-woocommerce.php';
