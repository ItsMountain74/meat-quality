<?php

namespace App\Providers;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Detectors\FailoverMeatDetector;
use App\Services\MeatScan\Detectors\MockMeatDetector;
use App\Services\MeatScan\Detectors\OpenAiVisionMeatDetector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MeatDetector::class, function () {
            $driver = (string) config('meatscan.driver', 'openai_vision');

            $fallback = $this->app->make(MockMeatDetector::class);

            return match ($driver) {
                'mock' => $fallback,
                default => new FailoverMeatDetector(
                    primary: $this->app->make(OpenAiVisionMeatDetector::class),
                    fallback: $fallback,
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
