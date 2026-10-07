<?php

declare(strict_types=1);

namespace BaseTheme\Tests\Unit;

use BaseTheme\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Unit tests for the pure-logic product helpers in inc/template-parts/products.php (analysis rows,
 * filling up related products, inquiry validation). Everything that queries posts/terms or renders
 * template parts stays out of scope, same boundary as CareersTest (see docs/to-do.md Abschnitt 1).
 */
final class ProductsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\stubTranslationFunctions();
    }

    public function test_analysis_rows_keep_order_and_drop_empty_rows(): void
    {
        $this->assertSame(
            [
                ['label' => 'Al₂O₃', 'value' => '93,5 – 95%'],
                ['label' => 'TiO₂', 'value' => ''],
                ['label' => '', 'value' => '0,5 %'],
            ],
            hengegroup_theme_normalize_analysis_rows(
                [' Al₂O₃ ', '', 'TiO₂', ''],
                ['93,5 – 95%', '  ', '', '0,5 %'],
            ),
        );
    }

    public function test_analysis_rows_tolerate_missing_values(): void
    {
        $this->assertSame(
            [['label' => 'SiO₂', 'value' => '']],
            hengegroup_theme_normalize_analysis_rows(['SiO₂'], []),
        );
    }

    public function test_related_ids_prefer_manual_then_fill_up(): void
    {
        $this->assertSame(
            [7, 3, 9, 11],
            hengegroup_theme_merge_related_product_ids([7, 3], [9, 11, 12], 1, 4),
        );
    }

    public function test_related_ids_skip_self_duplicates_and_invalid(): void
    {
        $this->assertSame(
            [3, 9, 12],
            hengegroup_theme_merge_related_product_ids([1, 3, 0, 3], [9, 3, 1, 12], 1, 4),
        );
    }

    public function test_related_ids_cap_manual_selection_at_limit(): void
    {
        $this->assertSame(
            [2, 3, 4, 5],
            hengegroup_theme_merge_related_product_ids([2, 3, 4, 5, 6], [7], 1, 4),
        );
    }

    public function test_anwendung_items_sort_by_group_then_order_then_name(): void
    {
        $sorted = hengegroup_theme_sort_anwendung_items([
            ['group_order' => 2, 'order' => 0, 'name' => 'Baubranche'],
            ['group_order' => 1, 'order' => 5, 'name' => 'Glasindustrie'],
            ['group_order' => 1, 'order' => 1, 'name' => 'Schleifmittelindustrie'],
            ['group_order' => 1, 'order' => 5, 'name' => 'Feuerfestindustrie'],
        ]);

        $this->assertSame(
            ['Schleifmittelindustrie', 'Feuerfestindustrie', 'Glasindustrie', 'Baubranche'],
            array_column($sorted, 'name'),
        );
    }

    private function inquiry(array $overrides = []): array
    {
        return array_merge(
            [
                'company' => 'Muster GmbH',
                'name' => 'Muster, Max',
                'postal_code' => '76877',
                'city' => 'Offenbach',
                'email' => 'max@example.com',
                'phone' => '+49 (0) 6348 / 1234',
                'message' => '',
                'privacy' => '1',
            ],
            $overrides,
        );
    }

    public function test_inquiry_accepts_complete_input(): void
    {
        $this->assertSame([], hengegroup_theme_validate_product_inquiry($this->inquiry(), true));
    }

    public function test_inquiry_requires_location_only_with_location(): void
    {
        $values = $this->inquiry(['postal_code' => '', 'city' => '']);

        $this->assertSame(
            ['postal_code', 'city'],
            array_keys(hengegroup_theme_validate_product_inquiry($values, true)),
        );
        $this->assertSame([], hengegroup_theme_validate_product_inquiry($values, false));
    }

    public function test_inquiry_reports_each_invalid_field(): void
    {
        $errors = hengegroup_theme_validate_product_inquiry(
            $this->inquiry([
                'company' => ' ',
                'name' => '',
                'email' => 'kein-mail',
                'phone' => 'abc',
                'privacy' => '',
            ]),
            false,
        );

        $this->assertSame(['company', 'name', 'email', 'phone', 'privacy'], array_keys($errors));
    }

    public function test_inquiry_phone_is_optional(): void
    {
        $this->assertSame(
            [],
            hengegroup_theme_validate_product_inquiry($this->inquiry(['phone' => '']), false),
        );
    }
}
