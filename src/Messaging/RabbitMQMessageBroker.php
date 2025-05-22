<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

class RabbitMQMessageBroker implements MessageBrokerInterface
{
    public function __construct() {}

    public function publish(string $channel, string $message, array $data = [])
    {
        // TODO: Implement publish() method.
    }

    public function subscribe(string $channel, callable $callback): void
    {
        // TODO: Implement subscribe() method.
    }
}
