<?php

namespace NobelzSushank\Bsad\Contracts;

use NobelzSushank\Bsad\Data\CalendarIndex;

interface CalendarDataProvider
{
    /**
     * Get the calendar index data.
     *
     * @return CalendarIndex
     */
    public function index(): CalendarIndex;
}