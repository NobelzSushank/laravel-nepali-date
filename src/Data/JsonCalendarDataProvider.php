<?php

namespace NobelzSushank\Bsad\Data;

use RuntimeException;

class JsonCalendarDataProvider implements CalendarDataProvider
{
    public function __construct(
        private string $dataPath,
        private string $fallbackPath
    ) {
    }

    public function index(): CalendarIndex
    {
        $path = file_exists($this->dataPath) ? $this->dataPath : $this->fallbackPath;
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Undable to read BSAD dataset at: {$path}");
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid json in BSAD dataset: {$path}");
        }

        return CalendarIndex::fromArray($data);
    }
}