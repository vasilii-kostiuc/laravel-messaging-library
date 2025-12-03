<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

use React\EventLoop\Loop;

class MessageBrokerFactory
{
    protected string $brokerType;

    public function __construct(string $brokerType)
    {
        $this->brokerType = $brokerType;
    }

    public function create(): MessageBrokerInterface
    {
        $redisHost = config('messaging.redis.host', '127.0.0.1');
        $redisPort = config('messaging.redis.port', 6);

        switch ($this->brokerType) {
            case 'redis':
                return new RedisMessageBroker(Loop::get(), $redisHost, $redisPort);
            case 'rabbitmq':
                return new RabbitMQMessageBroker;
            default:
                throw new \InvalidArgumentException("Unsupported broker: $this->defaultType");
        }
    }
}
