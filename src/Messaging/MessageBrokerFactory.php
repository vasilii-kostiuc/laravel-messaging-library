<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

class MessageBrokerFactory
{
    protected string $brokerType;

    public function __construct(string $brokerType)
    {
        $this->brokerType = $brokerType;
    }

    public function create(): MessageBrokerInterface
    {
        switch ($this->brokerType) {
            case 'redis':
                return new RedisMessageBroker;
            case 'rabbitmq':
                return new RabbitMQMessageBroker;
            default:
                throw new \InvalidArgumentException("Unsupported broker: $this->defaultType");
        }
    }
}
