<?php

namespace NobelzSushank\Bsad\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Formatting\Formatter;

final class CarbonMacros
{
    public static function register(): void
    {
        // AD -> BS
        Carbon::macro('toBs', function () {
            /** @var Carbon $this */
            return app(BsadConverter::class)->adToBs($this);
        });

        CarbonImmutable::macro('toBs', function () {
            /** @var CarbonImmutable $this */
            return app(BsadConverter::class)->adToBs($this);
        });

        // AD (Carbon) -> formatted BS string
        Carbon::macro('formatBs', function (string $pattern = 'Y-m-d', ?string $locale = null, ?bool $nepaliDigits = null) {
            /** @var Carbon $this */
            $bs = app(BsadConverter::class)->adToBs($this);
            return app(Formatter::class)->formatBs($bs, $pattern, $locale, $nepaliDigits);
        });

        CarbonImmutable::macro('formatBs', function (string $pattern = 'Y-m-d', ?string $locale = null, ?bool $nepaliDigits = null) {
            /** @var CarbonImmutable $this */
            $bs = app(BsadConverter::class)->adToBs($this);
            return app(Formatter::class)->formatBs($bs, $pattern, $locale, $nepaliDigits);
        });

        // AD (Carbon) -> formatted AD string but with locale-aware month/weekdays + optional nepali digits
        Carbon::macro('formatAdLocalized', function (string $pattern = 'Y-m-d', ?string $locale = null, ?bool $nepaliDigits = null) {
            /** @var Carbon $this */
            return app(Formatter::class)->formatAd($this->toImmutable(), $pattern, $locale, $nepaliDigits);
        });

        CarbonImmutable::macro('formatAdLocalized', function (string $pattern = 'Y-m-d', ?string $locale = null, ?bool $nepaliDigits = null) {
            /** @var CarbonImmutable $this */
            return app(Formatter::class)->formatAd($this, $pattern, $locale, $nepaliDigits);
        });
    }
}