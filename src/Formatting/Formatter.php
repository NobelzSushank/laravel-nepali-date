<?php

namespace NobelzSushank\Bsad\Formatting;

use Carbon\CarbonImmutable;
use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Support\Locale;
use NobelzSushank\Bsad\ValueObjects\BsDate;

class Formatter
{
    public function __construct(
        private BsadConverter $conv
    ) {
    }

    /**
     * Format a BS date according to the given pattern and locale.
     *
     * Supported pattern characters:
     * - Y: 4-digit year
     * - y: 2-digit year
     * - m: 2-digit month (01-12)
     * - n: month without leading zero (1-12)
     * - d: 2-digit day of month (01-31)
     * - j: day of month without leading zero (1-31)
     * - F: full month name in the specified locale
     * - l: full weekday name in the specified locale
     *
     * @param BsDate $bs
     * @param string $pattern
     * @param string $locale
     * @param bool $nepaliDigits
     *
     * @return string
     */
    public function formatBs(
        BsDate $bs,
        string $pattern = 'Y-m-d',
        ?string $locale = null,
        ?bool $nepaliDigits = null
    ): string {
        $locale ??= config('bsad.locale', Locale::EN);
        $nepaliDigits ??= (bool) config('bsad.nepali_digits', false);
        return (new BsFormatter($this->conv))->format($bs, $pattern, $locale, $nepaliDigits);
    }

    /**
     * Format an AD date according to the given pattern and locale.
     *
     * Supported pattern characters:
     * - Y: 4-digit year
     * - y: 2-digit year
     * - m: 2-digit month (01-12)
     * - n: month without leading zero (1-12)
     * - d: 2-digit day of month (01-31)
     * - j: day of month without leading zero (1-31)
     * - F: full month name in the specified locale
     * - l: full weekday name in the specified locale
     *
     * @param CarbonImmutable $ad
     * @param string $pattern
     * @param string|null $locale
     * @param bool|null $nepaliDigits
     *
     * @return string
     */
    public function formatAd(
        CarbonImmutable $ad,
        string $pattern = 'Y-m-d',
        ?string $locale = null,
        ?bool $nepaliDigits = null
    ): string {
        $locale ??= config('bsad.locale', Locale::EN);
        $nepaliDigits ??= (bool) config('bsad.nepali_digits', false);
        return (new AdFormatter())->format($ad, $pattern, $locale, $nepaliDigits);
    }
}