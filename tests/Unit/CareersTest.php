<?php

declare(strict_types=1);

namespace BaseTheme\Tests\Unit;

use BaseTheme\Tests\TestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the pure-logic job helpers in inc/template-parts/careers.php (formatting, expiry
 * rule, Google-JobPosting structure). Everything that reads post/term meta or renders template
 * parts stays out of scope here, same boundary as HelpersTest (see docs/to-do.md Abschnitt 1).
 */
final class CareersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\stubTranslationFunctions();
    }

    public static function listLinesProvider(): array
    {
        return [
            'plain lines' => ["Eins\nZwei", ['Eins', 'Zwei']],
            'strips bullets and blank lines' => [
                "- Eins\n\n• Zwei\r\n* Drei\n– Vier\n   ",
                ['Eins', 'Zwei', 'Drei', 'Vier'],
            ],
            'empty' => ['', []],
        ];
    }

    #[DataProvider('listLinesProvider')]
    public function test_parse_job_list_lines(string $input, array $expected): void
    {
        $this->assertSame($expected, hengegroup_theme_parse_job_list_lines($input));
    }

    public function test_location_label_uses_d_prefix_for_germany(): void
    {
        $this->assertSame(
            'D-06463 Falkenstein/Harz OT Ermsleben',
            hengegroup_theme_format_job_location_label([
                'postal_code' => '06463',
                'locality' => 'Falkenstein/Harz OT Ermsleben',
                'country' => 'DE',
            ]),
        );
    }

    public function test_location_label_uses_iso_code_abroad_and_falls_back_to_name(): void
    {
        $this->assertSame(
            'AT-1010 Wien',
            hengegroup_theme_format_job_location_label([
                'postal_code' => '1010',
                'locality' => 'Wien',
                'country' => 'at',
            ]),
        );
        $this->assertSame(
            'Offenbach',
            hengegroup_theme_format_job_location_label(['name' => 'Offenbach']),
        );
    }

    public static function salaryProvider(): array
    {
        return [
            'range' => [3200.0, 3800.0, 'MONTH', '3.200 – 3.800 € brutto pro Monat'],
            'swapped range' => [3800.0, 3200.0, 'MONTH', '3.200 – 3.800 € brutto pro Monat'],
            'min only' => [15.5, null, 'HOUR', 'ab 15,50 € brutto pro Stunde'],
            'max only' => [null, 50000.0, 'YEAR', 'bis 50.000 € brutto pro Jahr'],
            'fixed' => [3000.0, 3000.0, 'MONTH', '3.000 € brutto pro Monat'],
            'unknown unit falls back to month' => [
                3000.0,
                null,
                'DECADE',
                'ab 3.000 € brutto pro Monat',
            ],
            'none' => [null, null, 'MONTH', ''],
        ];
    }

    #[DataProvider('salaryProvider')]
    public function test_format_job_salary(
        ?float $min,
        ?float $max,
        string $unit,
        string $expected,
    ): void {
        $this->assertSame($expected, hengegroup_theme_format_job_salary($min, $max, $unit));
    }

    public static function expiryProvider(): array
    {
        return [
            'no date, not filled' => ['', false, '2026-10-07', false],
            'filled' => ['', true, '2026-10-07', true],
            'valid through today' => ['2026-10-07', false, '2026-10-07', false],
            'valid through yesterday' => ['2026-10-06', false, '2026-10-07', true],
            'future' => ['2026-12-31', false, '2026-10-07', false],
        ];
    }

    #[DataProvider('expiryProvider')]
    public function test_is_job_expired_on(
        string $valid_through,
        bool $filled,
        string $today,
        bool $expected,
    ): void {
        $this->assertSame(
            $expected,
            hengegroup_theme_is_job_expired_on($valid_through, $filled, $today),
        );
    }

    public function test_is_valid_job_date(): void
    {
        $this->assertTrue(hengegroup_theme_is_valid_job_date('2026-02-28'));
        $this->assertFalse(hengegroup_theme_is_valid_job_date('2026-02-30'));
        $this->assertFalse(hengegroup_theme_is_valid_job_date('07.10.2026'));
    }

    public function test_missing_fields_lists_what_google_needs(): void
    {
        $missing = hengegroup_theme_get_job_posting_missing_fields([
            'company' => null,
            'locations' => [],
            'work_model' => 'onsite',
            'employment_types' => [],
            'valid_through' => '',
            'salary' => ['min' => null, 'max' => null],
        ]);

        $this->assertSame(
            ['Unternehmen', 'Standort', 'Anstellungsart', 'Gültig bis', 'Gehalt'],
            $missing,
        );
    }

    public function test_remote_jobs_do_not_require_a_location(): void
    {
        $missing = hengegroup_theme_get_job_posting_missing_fields(
            $this->job(['locations' => [], 'work_model' => 'remote']),
        );

        $this->assertSame([], $missing);
    }

    public function test_job_posting_schema_contains_google_required_and_recommended_fields(): void
    {
        $schema = hengegroup_theme_build_job_posting_schema($this->job(), $this->context());

        $this->assertSame('JobPosting', $schema['@type']);
        $this->assertSame('Mitarbeitende in der Produktion (m/w/d)', $schema['title']);
        $this->assertSame('<p>Text</p>', $schema['description']);
        $this->assertSame('2026-10-01', $schema['datePosted']);
        $this->assertSame('2026-12-31T23:59:59+01:00', $schema['validThrough']);
        $this->assertSame('FULL_TIME', $schema['employmentType']);
        $this->assertSame(
            'KOMINEX Minerals + Processing GmbH & Co. KG',
            $schema['hiringOrganization']['name'],
        );
        $this->assertSame(
            'https://www.hengegroup.com/kominex',
            $schema['hiringOrganization']['sameAs'],
        );
        $this->assertSame('https://example.com/logo.svg', $schema['hiringOrganization']['logo']);
        $this->assertSame(
            'HENGEGROUP',
            $schema['hiringOrganization']['parentOrganization']['name'],
        );
        $this->assertSame('Am Selkebad 2', $schema['jobLocation']['address']['streetAddress']);
        $this->assertSame('DE', $schema['jobLocation']['address']['addressCountry']);
        $this->assertSame(51.73, $schema['jobLocation']['geo']['latitude']);
        $this->assertSame(
            [
                '@type' => 'QuantitativeValue',
                'unitText' => 'MONTH',
                'minValue' => 2800.0,
                'maxValue' => 3200.0,
            ],
            $schema['baseSalary']['value'],
        );
        $this->assertSame('EUR', $schema['baseSalary']['currency']);
        $this->assertSame('job-67', $schema['identifier']['value']);
        $this->assertSame('no requirements', $schema['experienceRequirements']);
        $this->assertSame(
            'professional certificate',
            $schema['educationRequirements']['credentialCategory'],
        );
        $this->assertSame('Vorsortierung, Qualitätskontrolle', $schema['responsibilities']);
        $this->assertSame('2026-10-01', $schema['jobStartDate']);
        $this->assertFalse($schema['directApply']);
        $this->assertArrayNotHasKey('jobLocationType', $schema);
    }

    public function test_job_posting_schema_for_remote_multi_type_single_salary_without_company(): void
    {
        $schema = hengegroup_theme_build_job_posting_schema(
            $this->job([
                'company' => null,
                'work_model' => 'remote',
                'employment_types' => ['FULL_TIME', 'PART_TIME'],
                'salary' => ['min' => 3000.0, 'max' => null, 'unit' => 'MONTH'],
                'valid_through' => '',
                'experience_months' => 24,
                'education' => '',
                'start_immediately' => false,
                'start_date' => '',
            ]),
            $this->context(),
        );

        $this->assertSame(['FULL_TIME', 'PART_TIME'], $schema['employmentType']);
        $this->assertSame('TELECOMMUTE', $schema['jobLocationType']);
        $this->assertSame('DE', $schema['applicantLocationRequirements']['name']);
        $this->assertSame('HENGEGROUP', $schema['hiringOrganization']['name']);
        $this->assertSame(3000.0, $schema['baseSalary']['value']['value']);
        $this->assertSame(24, $schema['experienceRequirements']['monthsOfExperience']);
        $this->assertArrayNotHasKey('validThrough', $schema);
        $this->assertArrayNotHasKey('educationRequirements', $schema);
        $this->assertArrayNotHasKey('jobStartDate', $schema);
    }

    private function job(array $overrides = []): array
    {
        return array_merge(
            [
                'id' => 67,
                'title' => 'Mitarbeitende in der Produktion (m/w/d)',
                'url' => 'https://example.com/karriere/produktion/',
                'date_posted' => '2026-10-01',
                'company' => [
                    'term_id' => 3,
                    'name' => 'KOMINEX',
                    'legal_name' => 'KOMINEX Minerals + Processing GmbH & Co. KG',
                    'website' => 'https://www.hengegroup.com/kominex',
                    'logo_id' => 9,
                    'variant' => 'henge-blue',
                    'benefits' => [],
                    'contact' => [],
                ],
                'locations' => [
                    [
                        'name' => 'Ermsleben',
                        'street' => 'Am Selkebad 2',
                        'postal_code' => '06463',
                        'locality' => 'Ermsleben',
                        'region' => 'Sachsen-Anhalt',
                        'country' => '',
                        'latitude' => '51.73',
                        'longitude' => '11.33',
                    ],
                ],
                'category' => 'Produktion',
                'employment_types' => ['FULL_TIME'],
                'valid_through' => '2026-12-31',
                'start_date' => '',
                'start_immediately' => true,
                'salary' => ['min' => 2800.0, 'max' => 3200.0, 'unit' => 'MONTH'],
                'work_model' => 'onsite',
                'experience_months' => 0,
                'education' => 'professional certificate',
                'tasks' => ['Vorsortierung', 'Qualitätskontrolle'],
                'profile' => ['Technisches Verständnis'],
                'benefits' => ['27 Urlaubstage'],
                'filled' => false,
                'legacy_id' => 'job-67',
                'reference' => 'job-67',
            ],
            $overrides,
        );
    }

    private function context(): array
    {
        return [
            'description_html' => '<p>Text</p>',
            'site_name' => 'HENGEGROUP',
            'site_url' => 'https://www.hengegroup.com/',
            'timezone_offset' => '+01:00',
            'logo_url' => 'https://example.com/logo.svg',
            'direct_apply' => false,
        ];
    }
}
