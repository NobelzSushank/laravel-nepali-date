<?php

namespace NobelzSushank\Bsad\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use NobelzSushank\Bsad\Data\CalendarIndex;
use RuntimeException;

class UpdateDataCommand extends Command
{
    protected $signature = 'bs:update-data {--url=} {--path=}';
    protected $description = 'Download and replace the BSAD dataset JSON (with validation + optional backup).';

    public function handle(): int
    {
        $url = $this->option('url') ?: config('bsad.data_url');
        $path = $this->option('path') ?: config('bsad.data_path');

        if ($url) {
            $this->info("Fetching dataset from URL: {$url}");
            $json = $this->fetchFromUrl($url);
        } else {
            $bundled = $this->bundledDatasetPath();
            $this->info("No URL provided. Using bundled dataset: {$bundled}");
            $json = $this->fetchFromLocal($bundled);
        }

        // Validate
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->error("Invalid JSON dataset.");
            return self::FAILURE;
        }

        try {
            CalendarIndex::fromArray($data);
        } catch (\Throwable $e) {
            $this->error("Dataset validation failed: " . $e->getMessage());
            return self::FAILURE;
        }

        // Backup existing
        if (config('bsad.backup_on_update', true) && file_exists($path)) {
            $backup = $path . '.' . date('Ymd_His') . '.bak';
            if (@copy($path, $backup)) {
                $this->info("Backup created: {$backup}");
            } else {
                $this->warn("Could not create backup at {$backup} (continuing).");
            }
        }

        // Write new dataset
        @mkdir(dirname($path), 0777, true);

        $pretty = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($pretty === false || file_put_contents($path, $pretty) === false) {
            throw new RuntimeException("Failed writing dataset to {$path}");
        }

        $this->info("Dataset updated: {$path}");
        return self::SUCCESS;
    }

    private function fetchFromUrl(string $url): string
    {
        $resp = Http::timeout(30)->get($url);

        if (!$resp->ok()) {
            throw new RuntimeException("Failed HTTP {$resp->status()} while fetching dataset.");
        }

        return $resp->body();
    }

    private function fetchFromLocal(string $path): string
    {
        $json = @file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException("Unable to read bundled dataset at: {$path}");
        }
        return $json;
    }

    private function bundledDatasetPath(): string
    {
        $root = dirname(__DIR__, 2);
        return $root . '/resources/data/bsad.json';
    }
}