<?php

declare(strict_types=1);

// PHPUnit bootstrap for the Brain Monkey unit suite (see phpunit.xml's header comment). Loads
// Composer's autoloader (Brain Monkey/Mockery/PHPUnit itself) plus the plain, non-autoloaded
// helpers file under test -- inc/template-parts/helpers.php only *defines* functions at include
// time (no WP function calls happen until one of them is actually invoked inside a test), so
// requiring it here without WordPress loaded is safe; each test stubs the specific WP functions
// (esc_attr/esc_html/_doing_it_wrong) it needs via Brain\Monkey\Functions before calling into it.

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/template-parts/helpers.php';

// inc/setup/theme-svg-support.php is NOT required here, unlike helpers.php above: it has
// top-level add_filter() calls that fire at require time, and Brain Monkey only defines
// add_filter()/apply_filters() lazily inside Brain\Monkey\setUp() (see
// vendor/brain/monkey/inc/api.php), not eagerly via Composer's autoloader -- requiring it this
// early would hit "Call to undefined function add_filter()". See
// tests/Unit/SvgSupportTest.php's own setUp() for where it's required instead (after
// parent::setUp() has run Brain\Monkey\setUp() at least once).
