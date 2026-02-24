<?php

namespace NobelzSushank\Bsad\ValueObjects;

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
}

