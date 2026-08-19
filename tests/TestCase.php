<?php

declare(strict_types=1);

namespace BaseTheme\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Shared Brain Monkey setup/teardown for every unit test in tests/Unit -- wires WP function
 * stubbing/expectation-tracking in and resets it after each test so mocked WP functions
 * (esc_attr(), _doing_it_wrong(), ...) never leak between test cases.
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
