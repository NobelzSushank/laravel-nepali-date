<?php

namespace NobelzSushank\Bsad\Formatting;

use Carbon\CarbonImmutable;
use NobelzSushank\Bsad\Support\Locale;
use NobelzSushank\Bsad\Support\NepaliDigits;

class AdFormatter
{
    public function format(
        CarbonImmutable $ad,
        string $pattern = 'Y-m-d',
        string $locale = 'en',
        bool $nepaliDigits = false
    ): string {
        $adMonths = Locale::adMonths($locale);
        $weekdays = Locale::weekdays($locale);

        $replacements = [
            'Y' => $ad->format('Y'),
            'y' => $ad->format('y'),
            'm' => $ad->format('m'),
            'n' => $ad->format('n'),
            'd' => $ad->format('d'),
            'j' => $ad->format('j'),
            'F' => $adMonths[(int) $ad->format('n') - 1] ?? '',
            'l' => $weekdays[$ad->dayOfWeek] ?? '',
        ];

        $formatted = strtr($pattern, $replacements);

        if ($nepaliDigits) {
            return NepaliDigits::toNepali($formatted);
        }

        return $formatted;
    }
}