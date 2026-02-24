<?php

namespace NobelzSushank\Bsad\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use NobelzSushank\Bsad\BsadServiceProvider;
use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Formatting\Formatter;
use NobelzSushank\Bsad\Support\Locale;
use Orchestra\Testbench\TestCase;

class BsadConverterTest extends TestCase
{
    public function getPackageProviders($app): array
    {
        return [BsadServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app->useStoragePath(__DIR__.'/tmp_storage');

        $app['config']->set('bsad.data_path', $app->storagePath('app/bsad/bsad.json'));
        $app['config']->set('bsad.update_url', null);
        $app['config']->set('bsad.backup_on_update', true);
        $app['config']->set('bsad.locale', Locale::EN);
        $app['config']->set('bsad.nepali_digits', false);

        $this->writeTestDataset($app->storagePath('app/bsad/bsad.json'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(__DIR__.'/tmp_storage');
        parent::tearDown();
    }

    private function writeTestDataset(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));

        // Minimal dataset for testing conversions
        // Anchor: BS 2000-01-01 == AD 1943-04-14
        $data = [
            'meta' => [
                'tz' => 'Asia/Kathmandu',
                'ad_anchor' => '1943-04-14',
                'bs_anchor' => ['y' => 2000, 'm' => 1, 'd' => 1],
                'source' => 'TEST',
                'version' => 'test-1.0',
            ],
            'years' => [
                2000 => [30, 32, 31, 31, 30, 30, 29, 30, 29, 30, 29, 30],
                2001 => [31, 31, 31, 31, 30, 30, 29, 30, 29, 30, 29, 30]
            ],
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function test_meta_contains_expected_fields(): void
    {
        $conv = $this->app->make(BsadConverter::class);
        $meta = $conv->meta();

        $this->assertSame('Asia/Kathmandu', $meta['tz']);
        $this->assertSame('1943-04-14', $meta['ad_anchor']);
        $this->assertSame('2000-1-1', $meta['bs_anchor']);
        $this->assertSame(2000, $meta['min_bs_year']);
        $this->assertSame(2001, $meta['max_bs_year']);
    }

    public function test_bs_to_ad_anchor_is_exact(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        $ad = $conv->bsToAd(2000, 1, 1);
        $this->assertInstanceOf(CarbonImmutable::class, $ad);
        $this->assertSame('1943-04-14', $ad->toDateString());
    }

    public function test_ad_to_bs_anchor_is_exact(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        $bs = $conv->adToBs('1943-04-14');
        $this->assertSame('2000-01-01', (string)$bs);
    }

    public function test_bs_to_ad_within_same_month(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        // BS 2000-01-02 should be anchor + 1 day
        $this->assertSame('1943-04-15', $conv->bsToAdDateString(2000, 1, 2));
        $this->assertSame('1943-04-16', $conv->bsToAdDateString(2000, 1, 3));
    }

    public function test_bs_to_ad_across_month_boundary(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        // For BS 2000 month 1 length = 30 (from test dataset)
        // So BS 2000-01-30 is anchor + 29
        $this->assertSame('1943-05-13', $conv->bsToAdDateString(2000, 1, 30));
        // Next day: BS 2000-02-01 is anchor + 30
        $this->assertSame('1943-05-14', $conv->bsToAdDateString(2000, 2, 1));
    }

    public function test_ad_to_bs_round_trip_small_range(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        $dates = [
            '1943-04-14',
            '1943-04-15',
            '1943-05-14',
            '1943-06-01',
        ];

        foreach ($dates as $ad) {
            $bs = $conv->adToBs($ad);
            $back = $conv->bsToAdDateString($bs->year, $bs->month, $bs->day);
            $this->assertSame($ad, $back, "Round-trip failed for AD {$ad}, got BS {$bs}");
        }
    }

    public function test_bs_validation_rejects_invalid_day(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        $this->expectException(\RuntimeException::class);
        // Month 1 in year 2000 has 30 days (test dataset)
        $conv->bsToAd(2000, 1, 31);
    }

    public function test_bs_validation_rejects_unsupported_year(): void
    {
        $conv = $this->app->make(BsadConverter::class);

        $this->expectException(\RuntimeException::class);
        $conv->bsToAd(1999, 1, 1);
    }

    public function test_formatter_bs_english_and_nepali_digits(): void
    {
        $conv = $this->app->make(BsadConverter::class);
        $fmt = new Formatter($conv);

        $bs = $conv->adToBs('1943-04-14'); // 2000-01-01

        $en = $fmt->formatBs($bs, 'Y-m-d F', 'en', false);
        $this->assertStringContainsString('2000-01-01', $en);

        $npDigits = $fmt->formatBs($bs, 'Y-m-d', 'np', true);
        $this->assertSame('२०००-०१-०१', $npDigits);
    }

    public function test_formatter_ad_nepali_locale_and_digits(): void
    {
        $fmt = $this->app->make(Formatter::class);

        $ad = CarbonImmutable::parse('1943-04-14', 'Asia/Kathmandu');
        $out = $fmt->formatAd($ad, 'Y-m-d', 'np', true);

        $this->assertSame('१९४३-०४-१४', $out);
    }

}