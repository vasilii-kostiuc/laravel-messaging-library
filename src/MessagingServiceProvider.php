<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary;

use Illuminate\Support\ServiceProvider;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;

class MessagingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MessageBrokerFactory::class, function ($app){
            return new MessageBrokerFactory(config('messaging.default'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
