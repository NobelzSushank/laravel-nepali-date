<?php

namespace NobelzSushank\Bsad;

use Illuminate\Support\ServiceProvider;
use NobelzSushank\Bsad\Console\UpdateDataCommand;
use NobelzSushank\Bsad\Contracts\CalendarDataProvider;
use NobelzSushank\Bsad\Converters\BsadConverter;
use NobelzSushank\Bsad\Data\JsonCalendarDataProvider;
use NobelzSushank\Bsad\Formatting\Formatter;

class BsadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bsad.php', 'bsad');

        $this->app->singleton(CalendarDataProvider::class, function() {
            return new JsonCalendarDataProvider(
                config('bsad.data_path'),
                __DIR__ . '/../resources/data/bsad.json'
            );
        });

        $this->app->singleton(BsadConverter::class, function ($app) {
            return new BsadConverter($app->make(CalendarDataProvider::class));
        });

        $this->app->singleton(Formatter::class, function ($app) {
            return new Formatter($app->make(BsadConverter::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/bsad.php' => config_path('bsad.php'),
        ], 'bsad-config');

        $this->publishes([
            __DIR__ . '/../resources/data/bsad.json' => storage_path('app/bsad/bsad.json'),
        ], 'bsad-data');

        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateDataCommand::class,
            ]);
        }
    }
}