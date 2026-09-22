<?php

declare(strict_types=1);

namespace BaseTheme\Tests\Unit;

use BaseTheme\Tests\TestCase;

/**
 * Unit tests for hengegroup_theme_get_svg_dimensions() (inc/setup/theme-svg-support.php) -- the
 * only pure logic in that file (no WP function calls inside it, see its own docblock). Everything
 * else in that file (upload_mimes/wp_check_filetype_and_ext/wp_handle_upload_prefilter/
 * wp_generate_attachment_metadata wiring, the actual enshrined/svg-sanitize call) needs a real
 * file upload/WordPress request cycle and is intentionally left to a future WP-backed integration
 * suite instead (see docs/to-do.md Abschnitt 1), same boundary as
 * hengegroup_theme_render_icon()/hengegroup_theme_render_image() in HelpersTest.php.
 */
final class SvgSupportTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Brain Monkey only defines add_filter() (used at the top of theme-svg-support.php) lazily
        // inside parent::setUp()'s Brain\Monkey\setUp() call, not eagerly via Composer's
        // autoloader -- this file can only be require_once'd safely from here on, see
        // tests/bootstrap.php's own comment for why it isn't loaded there instead.
        require_once dirname(__DIR__, 2) . '/inc/setup/theme-svg-support.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function writeTempSvg(string $content): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'hengegroup-theme-svg-test-');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_reads_width_and_height_attributes(): void
    {
        $path = $this->writeTempSvg(
            '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80"></svg>',
        );

        $this->assertSame(
            ['width' => 120, 'height' => 80],
            hengegroup_theme_get_svg_dimensions($path),
        );
    }

    public function test_rounds_fractional_width_and_height_attributes(): void
    {
        $path = $this->writeTempSvg(
            '<svg xmlns="http://www.w3.org/2000/svg" width="120.6" height="79.4"></svg>',
        );

        $this->assertSame(
            ['width' => 121, 'height' => 79],
            hengegroup_theme_get_svg_dimensions($path),
        );
    }

    public function test_falls_back_to_viewbox_when_width_and_height_carry_units(): void
    {
        $path = $this->writeTempSvg(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100px" height="50px" ' .
                'viewBox="0 0 200 150"></svg>',
        );

        $this->assertSame(
            ['width' => 200, 'height' => 150],
            hengegroup_theme_get_svg_dimensions($path),
        );
    }

    public function test_falls_back_to_viewbox_when_width_and_height_are_missing(): void
    {
        $path = $this->writeTempSvg(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0,0,64,32"></svg>',
        );

        $this->assertSame(
            ['width' => 64, 'height' => 32],
            hengegroup_theme_get_svg_dimensions($path),
        );
    }

    public function test_returns_null_for_svg_without_any_size_information(): void
    {
        $path = $this->writeTempSvg('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->assertNull(hengegroup_theme_get_svg_dimensions($path));
    }

    public function test_returns_null_for_invalid_xml(): void
    {
        $path = $this->writeTempSvg('not an svg at all <<<');

        $this->assertNull(hengegroup_theme_get_svg_dimensions($path));
    }

    public function test_returns_null_for_missing_file(): void
    {
        $this->assertNull(
            hengegroup_theme_get_svg_dimensions(sys_get_temp_dir() . '/does-not-exist.svg'),
        );
    }
}
