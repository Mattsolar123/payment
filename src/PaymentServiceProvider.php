<?php

namespace Mralston\Payment;

use Mralston\Payment\Console\Commands\PollPaymentStatus;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Sanctum\Sanctum;
use Mralston\Payment\Integrations\Hometree;
use Mralston\Payment\Integrations\Hsbc;
use Mralston\Payment\Integrations\Propensio;
use Mralston\Payment\Integrations\Tandem;
use Mralston\Payment\Interfaces\PaymentAddressLookup;
use Mralston\Payment\Interfaces\PaymentHelper;
use Mralston\Payment\Models\PersonalAccessToken;
use Mralston\Payment\Providers\EventServiceProvider;
use Mralston\Payment\Services\MugService;

class PaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'payment');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Conditionally configure Sanctum if not already configured
        $this->configureSanctum();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('payment.php'),
            ], 'payment-config');

            $this->publishes([
                __DIR__ . '/../database/seeders' => database_path('seeders'),
            ], 'payment-seeders');

            $this->publishes([
                __DIR__.'/../public/vendor/mralston/payment/build' => public_path('vendor/mralston/payment/build'),
            ], 'payment-assets');
        }

        // Conditionally set Inertia root view based on config
        if ($rootView = config('payment.inertia_root_view')) {
            Inertia::setRootView($rootView);
        } else {
            // Default package root view
            Inertia::setRootView('payment::app');
        }

        $this->app->bind(PaymentHelper::class, config('payment.helper'));
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'payment');

        $this->app->register(EventServiceProvider::class);

        $this->commands([
            PollPaymentStatus::class,
        ]);

        $this->app->singleton(PaymentHelper::class, function ($app) {
            return app(config('payment.helper'));
        });

        $this->app->singleton(Tandem::class, function ($app) {
            $helper = app(PaymentHelper::class);

            return new Tandem(
                $helper->getApiKey('tandem') ?? config('payment.tandem.api_key'),
                config('payment.tandem.endpoint'),
            );
        });

        $this->app->singleton(Propensio::class, function ($app) {
            $helper = app(PaymentHelper::class);

            return new Propensio(
                $helper->getApiKey('propensio') ?? config('payment.propensio.api_key'),
                config('payment.propensio.endpoint'),
            );
        });

        $this->app->singleton(Hsbc::class, function ($app) {
            $helper = app(PaymentHelper::class);

            return new Hsbc(
                $helper->getApiKey('hsbc') ?? config('payment.hsbc.api_key'),
                config('payment.hsbc.endpoint'),
            );
        });

        $this->app->singleton(Hometree::class, function ($app) {
            $helper = app(PaymentHelper::class);

            return new Hometree(
                $helper->getApiKey('hometree') ?? config('payment.hometree.api_key'),
                config('payment.hometree.endpoint'),
            );
        });

        $this->app->bind(PaymentAddressLookup::class, function ($app) {
            return app(MugService::class);
        });
    }

    /**
     * Configure Sanctum if it hasn't been configured already
     */
    protected function configureSanctum()
    {
        // Check if Sanctum is already configured by looking for the config
        if (!config('sanctum.stateful')) {
            // Set default Sanctum configuration
            config([
                'sanctum.stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
                    '%s%s',
                    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
                    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
                ))),
                'sanctum.guard' => ['web'],
                'sanctum.expiration' => null,
                'sanctum.middleware' => [
                    'verify_csrf_token' => \App\Http\Middleware\VerifyCsrfToken::class,
                    'encrypt_cookies' => \App\Http\Middleware\EncryptCookies::class,
                ],
            ]);
        }

        // Only set the PersonalAccessToken model if it hasn't been set already
        if (!class_exists(Sanctum::$personalAccessTokenModel)) {
            Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        }
    }
}
