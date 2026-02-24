<?php

namespace NobelzSushank\Bsad\Contracts;

interface CalendarDataProvider
{
    public function index(): CalendarIndex;
}