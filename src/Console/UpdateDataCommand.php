<?php

namespace NobelzSushank\Bsad\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use NobelzSushank\Bsad\Data\CalendarIndex;
use RuntimeException;

class UpdateDataCommand extends Command
{
    protected $signature = 'bs:update-data {--url=} {--path=} {--token=}';
    protected $description = 'Download and replace the BSAD dataset JSON (with validation + optional backup).';

    public function handle(): int
    {
        $url = $this->option('url') ?: config('bsad.data_url');
        $path = $this->option('path') ?: config('bsad.data_path');
        $token = $this->option('token') ?: env("GITHUB_TOKEN");

        if (!$url) {
            $this->error('No update URL provided. Set BSAD_UPDATE_URL or pass --url=');
            return self::FAILURE;
        }

        $this->info("Fetching dataset from: {$url}");

        $resp = Http::timeout(30);

        if ($token) {
            $resp = $resp->withToken($token);
        }

        $resp = $resp->get($url);
        
        if (!$resp->ok()) {
            $this->error("Failed to fetch dataset. HTTP status: {$resp->status()}");
            return self::FAILURE;
        }

        $json = $resp->body();
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->error("Invalid JSON from URL.");
            return self::FAILURE;
        }

        try {
            CalendarIndex::fromArray($data);
        } catch (\Throwable $e) {
            $this->error("Dataset validation failed: " . $e->getMessage());
            return self::FAILURE;
        }

        if (config('bsad.backup_before_update', true) && file_exists($path)) {
            $backup = $path . '.' . date('Ymd_His') . '.bak';
            if (!@copy($path, $backup)) {
                $this->error("Could not create backup at {$backup} (continuing with update).");
            } else {
                $this->info("Backup created: {$backup}");
            }
        }

        @mkdir(dirname($path), 0777, true);

        if (file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new RuntimeException("Failed writing dataset to {$path}");
        }
        
        $this->info("Dataset updated successfully at: {$path}");
        return self::SUCCESS;
    }
}