<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\RedisMessageBroker;

class RedisMessageBrokerTest extends TestCase
{
    public function testPublish(): void
    {
        // Arrange
        $broker = new RedisMessageBroker();
        $channel = 'test_channel';
        $message = 'test_message';
        $data = ['key' => 'value'];

        // Ожидаем, что будет отправлено сообщение в Redis
        Redis::shouldReceive('publish')
            ->once()
            ->with($channel, json_encode([
                'message' => $message,
                'data' => $data
            ]))
            ->andReturn(1);

        $broker->publish($channel, $message, $data);
    }
}
