<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

use Illuminate\Support\Facades\Redis;

class RedisMessageBroker implements MessageBrokerInterface
{
    public function __construct() {}

    public function publish(string $channel, string $message, array $data = [])
    {
        Redis::publish($channel, json_encode([
            'message' => $message,
            'data' => $data,
        ]
        ));
    }

    public function subscribe(string $channel, callable $callback): void
    {
        Redis::subscribe([$channel], function ($message) use ($callback) {
            $data = json_decode($message, true);
            $callback($data['message'], $data['data']);
        });
    }
}
