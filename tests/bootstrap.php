<?php

declare(strict_types=1);

// Loads the helper files under test; they only define functions, so no WordPress is needed.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/template-parts/helpers.php';
require_once __DIR__ . '/../inc/template-parts/woocommerce-product-card.php';
require_once __DIR__ . '/../inc/template-parts/careers.php';

// theme-svg-support.php is required in SvgSupportTest::setUp(): its add_filter() calls need
// Brain Monkey to be set up first.
require_once __DIR__ . '/../inc/template-parts/products.php';
