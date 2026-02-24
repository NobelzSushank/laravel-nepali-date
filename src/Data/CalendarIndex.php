<?php

namespace NobelzSushank\Bsad\Data;

use Carbon\CarbonImmutable;
use RuntimeException;

class CalendarIndex
{
    /**
     * @var array
     */
    public array $years = [];

    /**
     * @var array 
     */
    public array $yearTotals = [];

    public string $tz;
    public CarbonImmutable $adAnchor;
    public int $bsAnchorY;
    public int $bsAnchorM;
    public int $bsAnchorD;

    public string $source;
    public string $version;

    /**
     * @param array $data
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        foreach (['meta', 'years'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RuntimeException("BSAD dataset missing key: {$key}");
            }
        }

        $meta = $data['meta'];
        $years = $data['years'];

        if (!is_array($meta) || !is_array($years)) {
            throw new RuntimeException("BSAD dataset meta/years must be objects.");
        }

        $tz = (string)($meta['tz'] ?? 'Asia/Kathmandu');
        $adAnchor = CarbonImmutable::parse((string) $meta['ad_anchor'], $tz)->startOfDay();

        $bsAnchor = $meta['bs_anchor'] ?? null;

        if (!is_array($bsAnchor) || !isset($bsAnchor['Y'], $bsAnchor['m'], $bsAnchor['d'])) {
            throw new RuntimeException("BSAD dataset meta.bs_anchor invalid.");
        }

        $self = new self();
        $self->tz = $tz;
        $self->adAnchor = $adAnchor;
        $self->bsAnchorY = (int) $bsAnchor['y'];
        $self->bsAnchorM = (int) $bsAnchor['m'];
        $self->bsAnchorD = (int) $bsAnchor['d'];
        $self->source = (string) ($meta['source'] ?? '');
        $self->version = (string) ($meta['version'] ?? '');

        foreach ($years as $yStr => $months) {
            $y = (int) $yStr;

            if (!is_array($months) || count($months) !== 12) {
                throw new RuntimeException("BSAD dataset year {$y} must have 12 months.");
            }

            $monthDays = [];
            $total = 0;
            foreach (array_values($months) as $i => $d) {
                $d = (int) $d;
                if ($d < 28 || $d > 32) {
                    throw new RuntimeException("BSAD dataset year {$y} month " . ($i+1) . " day-count looks invalid: {$d}");
                }
                $monthDays[$i + 1] = $d;
                $total += $d;
            }
            $self->years[$y] = $monthDays;
            $self->yearTotals[$y] = $total;
        }

        ksort($self->years);
        ksort($self->yearTotals);

        // Ensure anchor year exists
        if (!isset($self->years[$self->bsAnchorY])) {
            throw new RuntimeException("BSAD dataset doesnot include anchor BS year {$self->bsAnchorY}.");
        }

        return $self;

    }

    /**
     * @param int $bsYear
     *
     * @return bool
     */
    public function hasYear(int $bsYear): bool
    {
        return isset($this->years[$bsYear]);
    }

    /**
     * @param int $bsYear
     * @param int $bsMonth
     *
     * @return int
     */
    public function daysInMonth(int $bsYear, int $bsMonth): int
    {
        if (!isset($this->years[$bsYear][$bsMonth])) {
            throw new RuntimeException("Unsupported BS year/month: {$bsYear}-{$bsMonth}");
        }
        return $this->years[$bsYear][$bsMonth];
    }

    /**
     * @return int
     */
    public function minYear(): int
    {
        return (int) array_key_first($this->years);
    }

    /**
     * @return int
     */
    public function maxYear(): int
    {
        return (int) array_key_last($this->years);
    }
}