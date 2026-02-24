<?php

namespace NobelzSushank\Bsad\Converters;

use Carbon\CarbonImmutable;
use RuntimeException;

class BsadConverter
{
    private CalendarIndex $idx;

    public function __construct(
        CalendarDataProvider $provider
    ) {
        $this->idx = $provider->index();
    }

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

    public function bsToAd(
        int $bsYear,
        int $bsMonth,
        int $bsDay
    ): CarbonImmutable {
        $this->assertValidBs($bsYear, $bsMonth, $bsDay);

        $offsetDays = $this->bsDaysFromAnchor($bsYear, $bsMonth, $bsDay);

        return $this->idx->adAnchor->addDays($offsetDays);
    }

    public function adToBs(string|\DateTimeInterface $adDate): BsDate
    {
        $ad = CarbonImmutable::parse($adDate, $this->idx->tz)->startOfDay();
        $diff = $this->idx->adAnchor->diffInDays($ad, false);

        return $this->bsFromAnchorOffset($diff);
    }

    public function bsToAdDateString(int $y, int $m, int $d): string
    {
        return $this->bsToAd($y, $m, $d)->toDateString();
    }

    public function adToBsString(string|\DateTimeInterface $adDate): string
    {
        return (string) $this->adToBs($adDate);
    }

    private function assertValidBs(int $y, int $m, int $d): void
    {
        if (!$this->idx->hasYear($y)) {
            throw new RuntimeException("Unsupported BS year {$y}. Dataset supports {$this->idx->minYear}-{$this->idx->maxYear}.");
        }

        if ($m < 1 || $m > 12) {
            throw new RuntimeException("Invalid BS month {$m}.");
        }

        $maxD = $this->idx->daysInMonth($y, $m);
        if ($d < 1 || $d > $maxD) {
            throw new RuntimeException("Invalid BS day {$d} for {$y}-{$m}. Max is {$maxD}.");
        }
    }

    private function bsDaysFromAnchor(int $y, int $m, int $d): int
    {
        $ay = $this->idx->bsAnchorY;
        $am = $this->idx->bsAnchorM;
        $ad = $this->idx->bsAnchorD;

        if ($y === $ay && $m === $am && $d === $ad) {
            return 0;
        }

        $forward = $this->comapreBs($y, $m, $d, $ay, $am, $ad) > 0;

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


    private function remainingDaysInBsMonth(int $y, int $m, int $d): int
    {
        $max = $this->idx->daysInMonth($y, $m);
        return ($max - $d + 1);
    }

    private function daysBeforeInBsMonth(int $y, int $m, int $d): int
    {
        return $d;
    }

    private function remainingDaysInBsYear(int $y, int $m, int $d): int
    {
        $sum = $this->remainingDaysInBsMonth($y, $m, $d);
        for ($mm = $m + 1; $mm <= 12; $mm++) {
            $sum += $this->idx->daysInMonth($y, $mm);
        }

        return $sum;
    }

    private function daysBeforeInBsYear(int $y, int $m, int $d): int
    {
        $sum = $this->daysBeforeInBsMonth($y, $m, $d);
        for ($mm = $m - 1; $mm >= 1; $mm--) {
            $sum += $this->idx->daysInMonth($y, $mm);
        }
        return $sum;
    }

    private function compareBs(int $y1, int $m1, int $d1, int $y2, int $m2, int $d2): int
    {
        return [$y1, $m1, $d1] <=> [$y2, $m2, $d2];
    }

}