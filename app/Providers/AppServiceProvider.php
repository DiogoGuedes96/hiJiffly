<?php

namespace App\Providers;

use App\Clients\MewsAvailabilityEndpoint;
use App\Clients\MewsReservationsEndpoint;
use App\Clients\MewsResourceCategoriesEndpoint;
use App\Clients\MewsServicesEndpoint;
use App\Http\Responses\Interfaces\ApiResponseBuilderInterface;
use App\Http\Responses\StructuredApiResponseBuilder;
use App\Interfaces\MewsAvailabilityServiceInterface;
use App\Interfaces\ReservationServiceInterface;
use App\Services\MewsAvailabilityService;
use App\Services\ReservationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register API Response Builder
        $this->app->singleton(ApiResponseBuilderInterface::class, StructuredApiResponseBuilder::class);
        
        // Register Mews API endpoint clients as singletons
        $this->app->singleton(MewsServicesEndpoint::class);
        $this->app->singleton(MewsResourceCategoriesEndpoint::class);
        $this->app->singleton(MewsAvailabilityEndpoint::class);
        $this->app->singleton(MewsReservationsEndpoint::class);
        
        // Register Mews services
        $this->app->singleton(MewsAvailabilityServiceInterface::class, MewsAvailabilityService::class);
        
        // Register other services
        $this->app->bind(ReservationServiceInterface::class, ReservationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure API rate limiting: 100 requests per hour per API token
        RateLimiter::for('api', function (Request $request) {
            return Limit::perHour(config('api.rate_limit'))
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $responseBuilder = app(ApiResponseBuilderInterface::class);
                    
                    $retryAfter = $headers['Retry-After'] ?? 60;
                    
                    return $responseBuilder
                        ->error(
                            'rate_limit_exceeded',
                            'Too many requests. Please try again later.',
                            429,
                            [
                                'retry_after_seconds' => (int) $retryAfter,
                            ]
                        )
                        ->withHeaders($headers);
                });
        });
    }
}
