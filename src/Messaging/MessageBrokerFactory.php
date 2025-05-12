<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary;

use Illuminate\Support\Facades\Config;

class MessageBrokerFactory
{

    public static function create(): MessageBrokerInterface
    {
        $messagingBroker = Config::get('messaging.default');

        switch ($messagingBroker) {
            case 'redis':
                return new RedisMessageBroker();
            case 'rabbitmq':
                return new RabbitMQMessageBroker();
            default:
                throw new \InvalidArgumentException("Broker is not supported, Broker : ". $messagingBroker);
        }

    }
}
