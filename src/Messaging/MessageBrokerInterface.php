<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

interface MessageBrokerInterface
{
    public function publish(string $channel, string $message, array $data = []);
    public function subscribe(string $channel, callable $callback): void;
}
