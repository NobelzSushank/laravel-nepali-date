<?php

namespace NobelzSushank\Bsad\ValueObjects;

use Carbon\CarbonImmutable;
use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Formatting\Formatter;
use NobelzSushank\Bsad\Support\Locale;

class BsDate
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day
    ) {
    }

    /**
     * Convert the BsDate to an array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
        ];
    }

    /**
     * Convert the BsDate to a string representation in the format YYYY-MM-DD.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    /**
     * Format BS date (token-based; locale-aware; optional Nepali digits).
     *
     * Supports:
     *   $bs->format('Y F d, l', 'np', true)
     * Also supports:
     *   $bs->format('Y F d, l', true)   // boolean as 2nd arg = nepaliDigits
     */
    public function format(
        string $pattern = 'Y-m-d',
        string|bool|null $locale = null,
        ?bool $nepaliDigits = null
    ): string {
        // If caller does: format(pattern, true)
        if (is_bool($locale) && $nepaliDigits === null) {
            $nepaliDigits = $locale;
            $locale = null;
        }

        /** @var Formatter $fmt */
        $fmt = app(Formatter::class);

        return $fmt->formatBs(
            $this,
            $pattern,
            is_string($locale) ? $locale : null,
            $nepaliDigits
        );
    }

    /**
     * Convert this BS date to AD CarbonImmutable.
     */
    public function toAd(): CarbonImmutable
    {
        /** @var BsadConverter $conv */
        $conv = app(BsadConverter::class);
        return $conv->bsToAd($this);
    }

    /**
     * BS month name (English or Nepali).
     */
    public function monthName(?string $locale = null): string
    {
        $locale ??= config('bsad.locale', Locale::EN);

        $months = Locale::bsMonths($locale);
        return $months[$this->month - 1] ?? '';
    }

    /**
     * Weekday name (English or Nepali) for this BS date.
     * (Computed by converting to AD and using dayOfWeek.)
     */
    public function weekdayName(?string $locale = null): string
    {
        $locale ??= config('bsad.locale', Locale::EN);

        $ad = $this->toAd();
        $weekdays = Locale::weekdays($locale);

        return $weekdays[$ad->dayOfWeek] ?? '';
    }
}

