<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Order;
use App\Models\Address;
use App\Models\Review;
use App\Services\MailConfigurationService;
use App\Policies\ProductPolicy;
use App\Policies\OrderPolicy;
use App\Policies\AddressPolicy;
use App\Policies\ReviewPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        Address::class => AddressPolicy::class,
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(MailConfigurationService::class)->apply();

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        DB::whenQueryingForLongerThan(500, function ($connection, $event) {
            Log::warning('slow_query_detected', [
                'sql' => $event->sql,
                'bindings' => $event->bindings,
                'time_ms' => $event->time,
                'connection' => $connection->getName(),
            ]);
        });
    }
}
