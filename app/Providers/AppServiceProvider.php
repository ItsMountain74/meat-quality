<?php

namespace App\Providers;

use App\Services\MeatScan\Contracts\MeatDetector;
use App\Services\MeatScan\Detectors\FailoverMeatDetector;
use App\Services\MeatScan\Detectors\MockMeatDetector;
use App\Services\MeatScan\Detectors\OpenAiVisionMeatDetector;
use App\Services\MeatScan\Detectors\RoboflowUniverseMeatDetector;
use App\Services\MeatScan\Detectors\RoboflowWorkflowMeatDetector;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MeatDetector::class, function () {
            $driver = (string) config('meatscan.driver', 'roboflow_universe');

            $fallback = $this->app->make(MockMeatDetector::class);

            return match ($driver) {
                'mock' => $fallback,
                'roboflow_workflow' => new FailoverMeatDetector(
                    primary: $this->app->make(RoboflowWorkflowMeatDetector::class),
                    fallback: $fallback,
                ),
                'openai_vision' => new FailoverMeatDetector(
                    primary: $this->app->make(OpenAiVisionMeatDetector::class),
                    fallback: $fallback,
                ),
                default => new FailoverMeatDetector(
                    primary: $this->app->make(RoboflowUniverseMeatDetector::class),
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
