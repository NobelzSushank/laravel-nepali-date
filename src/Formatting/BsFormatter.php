<?php

namespace NobelzSushank\Bsad\Formatting;

use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Support\Locale;
use NobelzSushank\Bsad\Support\NepaliDigits;
use NobelzSushank\Bsad\ValueObjects\BsDate;

class BsFormatter
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
    public function format(
        BsDate $bs,
        string $pattern = 'Y-m-d',
        string $locale = 'en',
        bool $nepaliDigits = false
    ): string {

        // Use AD to get weekday
        $ad = $this->conv->bsToAd($bs);

        $bsMonths = Locale::bsMonths($locale);
        $weekdays = Locale::weekdays($locale);

        $map = [
            'Y' => sprintf('%04d', $bs->year),
            'y' => substr(sprintf('%04d', $bs->year), -2),
            'm' => sprintf('%02d', $bs->month),
            'n' => (string) $bs->month,
            'd' => sprintf('%02d', $bs->day),
            'j' => (string) $bs->day,
            'F' => $bsMonths[$bs->month - 1] ?? '',
            'l' => $weekdays[$ad->dayOfWeek] ?? '',
        ];

        $out = '';
        $len = strlen($pattern);

        for ($i = 0; $i < $len; $i++) {
            $ch = $pattern[$i];

            // Allow escaping: \Y prints literal "Y"
            if ($ch === '\\' && $i + 1 < $len) {
                $out .= $pattern[$i + 1];
                $i++;
                continue;
            }

            $out .= $map[$ch] ?? $ch;
        }

        return $nepaliDigits ? NepaliDigits::toNepali($out) : $out;
    }
}