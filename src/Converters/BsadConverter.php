<?php

namespace NobelzSushank\Bsad\Converters;

use Carbon\CarbonImmutable;
use NobelzSushank\Bsad\Contracts\CalendarDataProvider;
use NobelzSushank\Bsad\Data\CalendarIndex;
use NobelzSushank\Bsad\ValueObjects\BsDate;
use RuntimeException;

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
     * @param int $bsYear
     * @param int $bsMonth
     * @param int $bsDay
     *
     * @return CarbonImmutable
     * @throws RuntimeException if the input BS date is invalid or out of supported range.
     */
    public function bsToAd(
        int $bsYear,
        int $bsMonth,
        int $bsDay
    ): CarbonImmutable {
        $this->assertValidBs($bsYear, $bsMonth, $bsDay);

        $offsetDays = $this->bsDaysFromAnchor($bsYear, $bsMonth, $bsDay);

        return $this->idx->adAnchor->addDays($offsetDays);
    }

    /**
     * Convert an AD date to BS.
     *
     * @param string|DateTimeInterface $adDate
     *
     * @return BsDate
     * @throws RuntimeException
     */
    public function adToBs(string|\DateTimeInterface $adDate): BsDate
    {
        $ad = CarbonImmutable::parse($adDate, $this->idx->tz)->startOfDay();
        $diff = $this->idx->adAnchor->diffInDays($ad, false);

        return $this->bsFromAnchorOffset($diff);
    }

    /**
     * Convenience methods for string outputs
     *
     * @param int $y
     * @param int $m
     * @param int $d
     *
     * @return string
     * @throws RuntimeException
     */
    public function bsToAdDateString(int $y, int $m, int $d): string
    {
        return $this->bsToAd($y, $m, $d)->toDateString();
    }

    /**
     * Convenience method for string output
     *
     * @param string|DateTimeInterface $adDate
     *
     * @return string
     * @throws RuntimeException
     */
    public function adToBsString(string|\DateTimeInterface $adDate): string
    {
        return (string) $this->adToBs($adDate);
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
        while ($cy < $y) {
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
                    $m = 1; $d = 1;
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