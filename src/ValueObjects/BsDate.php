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
     * Format BS date (locale + nepali digits).
     */
    public function format(string $pattern = 'Y-m-d', ?string $locale = null, ?bool $nepaliDigits = null): string
    {
        /** @var Formatter $fmt */
        $fmt = app(Formatter::class);
        return $fmt->formatBs($this, $pattern, $locale, $nepaliDigits);
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

