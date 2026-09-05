<?php

declare(strict_types=1);

namespace BaseTheme\Tests\Unit;

use BaseTheme\Tests\TestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the pure-logic helpers in inc/template-parts/helpers.php. Only
 * covers the helpers that are meaningfully testable without a full WordPress install:
 * hengegroup_theme_render_icon()/hengegroup_theme_render_image() call get_template_part() against real
 * template-parts files and are intentionally left to a future WP-backed integration suite instead
 * (see docs/to-do.md Abschnitt 1).
 */
final class HelpersTest extends TestCase
{
    /**
     * Stubs esc_attr()/esc_html() as pass-through for tests that assert on hengegroup_theme_*()'s own
     * assembly logic (attribute order, whitespace, which words get wrapped, ...), not on core's
     * escaping behaviour itself. Deliberately NOT a setUp() blanket stub: Mockery matches the
     * first still-open expectation added for a given function, so a test that instead needs a
     * strict Functions\expect(...)->once()->with(...) on the same function (see
     * test_render_attributes_escapes_every_string_value below) must skip calling this, or its
     * specific expectation would never be reached.
     */
    private function stubEscapingPassthrough(): void
    {
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
    }

    public function test_render_attributes_renders_string_and_bool_attributes(): void
    {
        $this->stubEscapingPassthrough();

        $result = hengegroup_theme_render_attributes([
            'class' => 'foo',
            'disabled' => true,
            'data-x' => 'y',
        ]);

        $this->assertSame(' class="foo" disabled data-x="y"', $result);
    }

    public function test_render_attributes_omits_null_and_false_values(): void
    {
        $this->stubEscapingPassthrough();

        $result = hengegroup_theme_render_attributes([
            'class' => 'foo',
            'hidden' => false,
            'aria-label' => null,
        ]);

        $this->assertSame(' class="foo"', $result);
    }

    public function test_render_attributes_returns_empty_string_for_no_attributes(): void
    {
        $this->assertSame('', hengegroup_theme_render_attributes([]));
    }

    public function test_render_attributes_skips_blank_attribute_names(): void
    {
        $this->stubEscapingPassthrough();

        $result = hengegroup_theme_render_attributes([
            '' => 'ignored',
            '  ' => 'ignored',
            'class' => 'foo',
        ]);

        $this->assertSame(' class="foo"', $result);
    }

    public function test_render_attributes_escapes_every_string_value(): void
    {
        // Deliberately not calling stubEscapingPassthrough() here -- a specific
        // Functions\expect()->with(...) and the passthrough's Functions\when() would both try to
        // handle esc_attr(), and Mockery resolves a call against the first still-open expectation,
        // so the two must not coexist for the same function within one test (see
        // stubEscapingPassthrough()'s doc comment above).
        Functions\expect('esc_attr')->once()->with('foo"bar')->andReturn('foo&quot;bar');

        $result = hengegroup_theme_render_attributes(['title' => 'foo"bar']);

        $this->assertSame(' title="foo&quot;bar"', $result);
    }

    public function test_render_accent_text_wraps_highlighted_words(): void
    {
        $this->stubEscapingPassthrough();

        $result = hengegroup_theme_render_accent_text('Build faster, ship sooner', [
            'faster',
            'sooner',
        ]);

        $this->assertSame(
            'Build <span class="font-accent">faster</span>, ship <span class="font-accent">sooner</span>',
            $result,
        );
    }

    public function test_render_accent_text_without_highlighted_words_still_escapes(): void
    {
        // Same reasoning as test_render_attributes_escapes_every_string_value() above, for
        // esc_html() instead of esc_attr().
        Functions\expect('esc_html')->once()->with('plain text')->andReturn('plain text');

        $result = hengegroup_theme_render_accent_text('plain text', []);

        $this->assertSame('plain text', $result);
    }

    public function test_render_accent_text_matches_longest_word_first(): void
    {
        $this->stubEscapingPassthrough();

        // 'ship' is a substring of nothing here, but 'faster' vs. a hypothetical shorter overlap
        // is exactly the case the longest-first sort in the implementation guards against -- this
        // asserts both needles still resolve to independent, correctly wrapped spans.
        $result = hengegroup_theme_render_accent_text('fast and faster', ['fast', 'faster']);

        $this->assertSame(
            '<span class="font-accent">fast</span> and <span class="font-accent">faster</span>',
            $result,
        );
    }

    public function test_render_accent_text_ignores_blank_highlighted_words(): void
    {
        $this->stubEscapingPassthrough();

        $result = hengegroup_theme_render_accent_text('hello world', ['', '   ']);

        $this->assertSame('hello world', $result);
    }

    public function test_field_description_id_appends_suffix(): void
    {
        $this->assertSame('email-description', hengegroup_theme_field_description_id('email'));
    }

    public function test_field_error_id_appends_suffix(): void
    {
        $this->assertSame('email-error', hengegroup_theme_field_error_id('email'));
    }

    public function test_field_describedby_combines_both_ids_by_default(): void
    {
        $this->assertSame(
            'email-description email-error',
            hengegroup_theme_field_describedby('email'),
        );
    }

    public function test_field_describedby_omits_missing_description(): void
    {
        $this->assertSame(
            'email-error',
            hengegroup_theme_field_describedby('email', has_description: false),
        );
    }

    public function test_field_describedby_omits_missing_error(): void
    {
        $this->assertSame(
            'email-description',
            hengegroup_theme_field_describedby('email', has_error: false),
        );
    }

    public function test_field_describedby_returns_empty_string_when_neither_exists(): void
    {
        $this->assertSame(
            '',
            hengegroup_theme_field_describedby('email', has_description: false, has_error: false),
        );
    }

    public function test_warn_missing_aria_label_fires_for_icon_only_without_label(): void
    {
        $this->stubEscapingPassthrough();
        Functions\expect('_doing_it_wrong')->once();

        hengegroup_theme_warn_missing_aria_label('button.php', true, '');

        // No assertion beyond the Functions\expect() above -- Brain Monkey fails the test in
        // tearDown() if the expected call didn't happen.
        $this->assertTrue(true);
    }

    public function test_warn_missing_aria_label_is_silent_when_label_present(): void
    {
        Functions\expect('_doing_it_wrong')->never();

        hengegroup_theme_warn_missing_aria_label('button.php', true, 'Close');

        $this->assertTrue(true);
    }

    public function test_warn_missing_aria_label_is_silent_when_not_icon_only(): void
    {
        Functions\expect('_doing_it_wrong')->never();

        hengegroup_theme_warn_missing_aria_label('button.php', false, '');

        $this->assertTrue(true);
    }

    public static function floatingPositionProvider(): array
    {
        return [
            'bottom/center default' => [
                'bottom',
                'center',
                10,
                'top-[calc(100%+10px)] left-1/2 -translate-x-1/2',
            ],
            'bottom/start' => ['bottom', 'start', 10, 'top-[calc(100%+10px)] left-0'],
            'bottom/end' => ['bottom', 'end', 10, 'top-[calc(100%+10px)] right-0'],
            'top/center' => [
                'top',
                'center',
                10,
                'bottom-[calc(100%+10px)] left-1/2 -translate-x-1/2',
            ],
            'top/start' => ['top', 'start', 10, 'bottom-[calc(100%+10px)] left-0'],
            'top/end' => ['top', 'end', 10, 'bottom-[calc(100%+10px)] right-0'],
            'left/center' => [
                'left',
                'center',
                10,
                'right-[calc(100%+10px)] top-1/2 -translate-y-1/2',
            ],
            'left/start' => ['left', 'start', 10, 'right-[calc(100%+10px)] top-0'],
            'left/end' => ['left', 'end', 10, 'right-[calc(100%+10px)] bottom-0'],
            'right/center' => [
                'right',
                'center',
                10,
                'left-[calc(100%+10px)] top-1/2 -translate-y-1/2',
            ],
            'right/start' => ['right', 'start', 10, 'left-[calc(100%+10px)] top-0'],
            'right/end' => ['right', 'end', 10, 'left-[calc(100%+10px)] bottom-0'],
            'custom gap' => [
                'bottom',
                'center',
                8,
                'top-[calc(100%+8px)] left-1/2 -translate-x-1/2',
            ],
        ];
    }

    #[DataProvider('floatingPositionProvider')]
    public function test_floating_position_classes_covers_every_side_align_combination(
        string $side,
        string $align,
        int $gapPx,
        string $expected,
    ): void {
        $this->assertSame(
            $expected,
            hengegroup_theme_floating_position_classes($side, $align, $gapPx),
        );
    }

    public function test_floating_position_classes_falls_back_to_bottom_for_unknown_side(): void
    {
        $this->assertSame(
            hengegroup_theme_floating_position_classes('bottom', 'center'),
            hengegroup_theme_floating_position_classes('diagonal', 'center'),
        );
    }

    public function test_floating_position_classes_falls_back_to_center_for_unknown_align(): void
    {
        $this->assertSame(
            hengegroup_theme_floating_position_classes('bottom', 'center'),
            hengegroup_theme_floating_position_classes('bottom', 'sideways'),
        );
    }

    public function test_floating_position_classes_defaults_align_to_center_and_gap_to_10(): void
    {
        $this->assertSame(
            'top-[calc(100%+10px)] left-1/2 -translate-x-1/2',
            hengegroup_theme_floating_position_classes('bottom'),
        );
    }
}
