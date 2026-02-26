<?php

namespace NobelzSushank\Bsad\Converters;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use NobelzSushank\Bsad\Contracts\CalendarDataProvider;
use NobelzSushank\Bsad\Data\CalendarIndex;
use NobelzSushank\Bsad\ValueObjects\BsDate;
use RuntimeException;

/**
 * Data-driven BS <-> AD converter.
 *
 * Flexible input supported for both directions:
 * - BS: (y,m,d) ints OR string "YYYY-MM-DD" OR array OR BsDate
 * - AD: Carbon/DateTimeInterface OR string parseable by Carbon OR array OR (y,m,d) ints
 */
class BsadConverter
{
    private CalendarIndex $idx;

    public function __construct(
        CalendarDataProvider $provider
    ) {
        $this->idx = $provider->index();
    }

    /**
     * Get metadata about the calendar dataset and configuration.
     *
     * @return array
     */
    public function meta(): array
    {
        return [
            'tz' => $this->idx->tz,
            'ad_anchor' => $this->idx->adAnchor->toDateString(),
            'bs_anchor' => "{$this->idx->bsAnchorY}-{$this->idx->bsAnchorM}-{$this->idx->bsAnchorD}",
            'min_bs_year' => $this->idx->minYear(),
            'max_bs_year' => $this->idx->maxYear(),
            'source' => $this->idx->source,
            'version' => $this->idx->version,
        ];
    }

    /**
     * Convert a BS date to AD.
     *
     * Supported inputs:
     * - bsToAd(2082, 11, 14)
     * - bsToAd('2082-11-14') (also 2082/11/14, 2082.11.14, etc.)
     * - bsToAd(['y'=>2082,'m'=>11,'d'=>14]) or ['year'=>..., 'month'=>..., 'day'=>...]
     * - bsToAd([2082,11,14])
     * - bsToAd(BsDate $bs)
     *
     * @param int|string|array|BsDate $bs
     * @param int $month
     * @param int $day
     *
     * @return CarbonImmutable
     * @throws RuntimeException if the input BS date is invalid or out of supported range.
     */
    public function bsToAd(
        int|string|array|BsDate $bs,
        ?int $month = null,
        ?int $day = null
    ): CarbonImmutable {
        
        [$y, $m, $d] = $this->normalizeBsInput($bs, $month, $day);

        $this->assertValidBs($y, $m, $d);

        $offsetDays = $this->bsDaysFromAnchor($y, $m, $d);

        return $this->idx->adAnchor->addDays($offsetDays);
    }

    public function bsToAdDateString(
        int|string|array|BsDate $bs,
        ?int $month = null,
        ?int $day = null
    ): string {
        return $this->bsToAd($bs, $month, $day)->toDateString();
    }
    

    /**
     * Convert an AD date to BS.
     *
     * Supported inputs:
     * - adToBs('2026-02-26') (anything Carbon can parse)
     * - adToBs(now())
     * - adToBs(CarbonImmutable::parse(...))
     * - adToBs([2026,2,26])
     * - adToBs(['y'=>2026,'m'=>2,'d'=>26]) or ['year'=>..., 'month'=>..., 'day'=>...]
     * - adToBs(2026, 2, 26)
     *
     * @param string|array|int|DateTimeInterface $ad
     * @param int|null $month
     * @param int|null $day
     *
     * @return BsDate
     * @throws RuntimeException
     */
    public function adToBs(
        string|array|int|DateTimeInterface $ad,
        ?int $month = null,
        ?int $day = null
    ): BsDate {
        $adDate = $this->normalizeAdInput($ad, $month, $day);
        
        
        $diff = $this->idx->adAnchor->diffInDays($adDate, false);

        return $this->bsFromAnchorOffset($diff);
    }

    /**
     * Convenience method for string output
     *
     * @param string|DateTimeInterface $adDate
     *
     * @return string
     * @throws RuntimeException
     */
    public function adToBsString(
        string|array|int|DateTimeInterface $ad,
        ?int $month = null,
        ?int $day = null
    ): string {
        return (string)$this->adToBs($ad, $month, $day);
    }

    private function normalizeBsInput(int|string|array|BsDate $bs, ?int $month, ?int $day): array
    {
        if ($bs instanceof BsDate) {
            return [$bs->year, $bs->month, $bs->day];
        }

        // bsToAd(YYYY, m, d)
        if (is_int($bs)) {
            if ($month === null || $day === null) {
                throw new RuntimeException("BS input invalid: when first argument is an int year, month and day are required.");
            }
            return [$bs, $month, $day];
        }

        // bsToAd('YYYY-MM-DD' or 'YYYY/MM/DD' etc.)
        if (is_string($bs)) {
            $parsed = $this->parseYmdString($bs);
            if ($parsed === null) {
                throw new RuntimeException("BS input invalid: string must look like YYYY-MM-DD (or similar). Given: {$bs}");
            }
            return $parsed;
        }

        // bsToAd(array)
        // Supports:
        // - ['y'=>, 'm'=>, 'd'=>]
        // - ['year'=>, 'month'=>, 'day'=>]
        // - [YYYY, m, d]
        if (array_is_list($bs)) {
            if (count($bs) < 3) {
                throw new RuntimeException("BS input invalid: list array must be [year, month, day].");
            }
            return [(int)$bs[0], (int)$bs[1], (int)$bs[2]];
        }

        $y = $bs['y'] ?? $bs['year'] ?? null;
        $m = $bs['m'] ?? $bs['month'] ?? null;
        $d = $bs['d'] ?? $bs['day'] ?? null;

        if ($y === null || $m === null || $d === null) {
            throw new RuntimeException("BS input invalid: associative array must contain y/m/d or year/month/day.");
        }

        return [(int)$y, (int)$m, (int)$d];
    }

    private function normalizeAdInput(string|DateTimeInterface|array|int $ad, ?int $month, ?int $day): CarbonImmutable
    {
        // adToBs(YYYY, m, d)
        if (is_int($ad)) {
            if ($month === null || $day === null) {
                throw new RuntimeException("AD input invalid: when first argument is an int year, month and day are required.");
            }
            return CarbonImmutable::create($ad, $month, $day, 0, 0, 0, $this->idx->tz)->startOfDay();
        }

        // adToBs(DateTimeInterface) - Carbon, DateTime, etc.
        if ($ad instanceof DateTimeInterface) {
            return CarbonImmutable::parse($ad, $this->idx->tz)->startOfDay();
        }

        // adToBs(string) - anything Carbon can parse
        if (is_string($ad)) {
            return CarbonImmutable::parse($ad, $this->idx->tz)->startOfDay();
        }

        // adToBs(array) - either [Y,m,d] or associative
        if (array_is_list($ad)) {
            if (count($ad) < 3) {
                throw new RuntimeException("AD input invalid: list array must be [year, month, day].");
            }
            return CarbonImmutable::create((int)$ad[0], (int)$ad[1], (int)$ad[2], 0, 0, 0, $this->idx->tz)->startOfDay();
        }

        $y = $ad['y'] ?? $ad['year'] ?? null;
        $m = $ad['m'] ?? $ad['month'] ?? null;
        $d = $ad['d'] ?? $ad['day'] ?? null;

        if ($y !== null && $m !== null && $d !== null) {
            return CarbonImmutable::create((int)$y, (int)$m, (int)$d, 0, 0, 0, $this->idx->tz)->startOfDay();
        }

        throw new RuntimeException("AD input invalid: array must be [year,month,day] or have y/m/d (or year/month/day).");
    }

    /**
     * Parse "YYYY-MM-DD" and similar separators (/, ., spaces).
     * Returns [Y, m, d] or null.
     */
    private function parseYmdString(string $s): ?array
    {
        $s = trim($s);

        // Accept separators like '-', '/', '.', or spaces. Example: 2082-11-14, 2082/11/14, 2082.11.14
        if (preg_match('/^(\d{4})\D+(\d{1,2})\D+(\d{1,2})$/', $s, $m)) {
            return [(int)$m[1], (int)$m[2], (int)$m[3]];
        }

        return null;
    }

    /**
     * Validate the given BS date components against the dataset's supported range and structure.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return void
     * @throws RuntimeException
     */
    private function assertValidBs(int $y, int $m, int $d): void
    {
        if (!$this->idx->hasYear($y)) {
            throw new RuntimeException("Unsupported BS year {$y}. Dataset supports {$this->idx->minYear()}-{$this->idx->maxYear()}.");
        }

        if ($m < 1 || $m > 12) {
            throw new RuntimeException("Invalid BS month {$m}.");
        }

        $maxD = $this->idx->daysInMonth($y, $m);
        if ($d < 1 || $d > $maxD) {
            throw new RuntimeException("Invalid BS day {$d} for {$y}-{$m}. Max is {$maxD}.");
        }
    }

    /**
     * Calculate the number of days between the given BS date and the anchor BS date.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return int
     */
    private function bsDaysFromAnchor(int $y, int $m, int $d): int
    {
        $ay = $this->idx->bsAnchorY;
        $am = $this->idx->bsAnchorM;
        $ad = $this->idx->bsAnchorD;

        if ($y === $ay && $m === $am && $d === $ad) {
            return 0;
        }

        $forward = $this->compareBs($y, $m, $d, $ay, $am, $ad) > 0;

        if ($forward) {
            $days = 0;

            $cy = $ay;
            $cm = $am;
            $cd = $ad;

            // move to start of next year
            while ($cy < $y) {
                $days += $this->remainingDaysInBsYear($cy, $cm, $cd);
                $cy++;
                $cm = 1;
                $cd = 1;
            }

            // move to same year next month
            while ($cm < $m) {
                $days += $this->remainingdaysInBsMonth($cy, $cm, $cd);
                $cm++;
                $cd = 1;
            }

            // same month
            $days += ($d - $cd);

            return $days;
        }

        //backward
        $days = 0;
        $cy = $ay;
        $cm = $am;
        $cd = $ad;

        // jump year backward
        while ($cy > $y) {
            $days += $this->daysBeforeInBsYear($cy, $cm, $cd);
            $cy--;
            $cm = 12;
            $cd = $this->idx->daysInMonth($cy, $cm);
        }

        // same year
        while ($cm > $m) {
            $days += $this->daysBeforeInBsMonth($cy, $cm, $cd);
            $cm--;
            $cd = $this->idx->daysInMonth($cy, $cm);
        }

        // now same month
        $days += ($cd - $d);

        return -$days;
    }

    /**
     * Calculate the BS date that is a given number of days offset from the anchor BS date.
     *
     * @param int
     *
     * @return BsDate
     * @throws RuntimeException
     */
    private function bsFromAnchorOffset(int $offsetDays): BsDate
    {
        $y = $this->idx->bsAnchorY;
        $m = $this->idx->bsAnchorM;
        $d = $this->idx->bsAnchorD;

        if ($offsetDays === 0) {
            return new BsDate($y, $m, $d);
        }

        if ($offsetDays > 0) {
            $remaining = $offsetDays;

            // jump whole year
            while ($remaining > 0) {
                if (!$this->idx->hasYear($y)) {
                    throw new RuntimeException("Dataset missing BS year {$y} while converting (forward).");
                }

                $daysLeftInYear = $this->remainingDaysInBsYear($y, $m, $d);
                if ($remaining >= $daysLeftInYear) {
                    $remaining -= $daysLeftInYear;
                    $y++;
                    $m = 1;
                    $d = 1;
                    continue;
                }
                break;
            }

            // month/day step with jumps
            while ($remaining > 0) {
                $daysLeftInMonth = $this->remainingDaysInBsMonth($y, $m, $d);
                if ($remaining >= $daysLeftInMonth) {
                    $remaining -= $daysLeftInMonth;
                    $m++;
                    if ($m > 12) { $y++; $m = 1; }
                    $d = 1;
                    continue;
                }
                // inside month
                $d += $remaining;
                $remaining = 0;
            }

            return new BsDate($y, $m, $d);
        }

        // offsetDays < 0
        $remaining = -$offsetDays;

        while ($remaining > 0) {
            if (!$this->idx->hasYear($y)) {
                throw new RuntimeException("Dataset missing BS year {$y} while converting (backward).");
            }

            $daysBeforeInYear = $this->daysBeforeInBsYear($y, $m, $d);
            if ($remaining > $daysBeforeInYear) {
                $remaining -= $daysBeforeInYear;
                $y--;
                $m = 12;
                if (!$this->idx->hasYear($y)) {
                    throw new RuntimeException("Dataset missing BS year {$y} while converting (backward).");
                }
                $d = $this->idx->daysInMonth($y, $m);
                continue;
            }
            break;
        }

        while ($remaining > 0) {
            $daysBeforeInMonth = $this->daysBeforeInBsMonth($y, $m, $d);
            if ($remaining > $daysBeforeInMonth) {
                $remaining -= $daysBeforeInMonth;
                $m--;
                if ($m < 1) { $y--; $m = 12; }
                if (!$this->idx->hasYear($y)) {
                    throw new RuntimeException("Dataset missing BS year {$y} while converting (backward).");
                }
                $d = $this->idx->daysInMonth($y, $m);
                continue;
            }
            // inside month
            $d -= $remaining;
            $remaining = 0;
        }

        return new BsDate($y, $m, $d);
    }

    /**
     * Calculate the number of days remaining in the given BS month from the given date.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return int
     */
    private function remainingDaysInBsMonth(int $y, int $m, int $d): int
    {
        $max = $this->idx->daysInMonth($y, $m);
        return ($max - $d + 1);
    }

    /**
     * Calculate the number of days that have passed in the given BS month up to the given date.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return int
     */
    private function daysBeforeInBsMonth(int $y, int $m, int $d): int
    {
        return $d;
    }

    /**
     * Calculate the number of days remaining in the given BS year from the given date.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return int
     */
    private function remainingDaysInBsYear(int $y, int $m, int $d): int
    {
        $sum = $this->remainingDaysInBsMonth($y, $m, $d);
        for ($mm = $m + 1; $mm <= 12; $mm++) {
            $sum += $this->idx->daysInMonth($y, $mm);
        }

        return $sum;
    }

    /**
     * Calculate the number of days that have passed in the given BS year up to the given date.
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return int
     */
    private function daysBeforeInBsYear(int $y, int $m, int $d): int
    {
        $sum = $this->daysBeforeInBsMonth($y, $m, $d);
        for ($mm = $m - 1; $mm >= 1; $mm--) {
            $sum += $this->idx->daysInMonth($y, $mm);
        }
        return $sum;
    }

    /**
     * Compare two BS dates.
     *
     * @param int $y1
     * @param int $m1
     * @param int $d1
     * @param int $y2
     * @param int $m2
     * @param int $d2
     *
     * @return int Returns -1 if the first date is earlier, 1 if later, or 0 if they are the same.
     */
    private function compareBs(int $y1, int $m1, int $d1, int $y2, int $m2, int $d2): int
    {
        return [$y1, $m1, $d1] <=> [$y2, $m2, $d2];
    }

}