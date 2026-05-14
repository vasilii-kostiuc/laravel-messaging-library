<?php

namespace Tests\Unit;

use React\EventLoop\Loop;
use Tests\TestCase;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\RedisMessageBroker;

class RedisMessageBrokerTest extends TestCase
{
    public function test_publish(): void
    {
        $loop = Loop::get();
        $broker = new RedisMessageBroker($loop, (string) (getenv('REDIS_HOST') ?: '127.0.0.1'), (int) (getenv('REDIS_PORT') ?: 6379));
        $channel = 'test_channel';
        $message = 'test_message';
        $data = ['key' => 'value'];

        $messageReceived = false;

        $broker->subscribe($channel, function ($msg, $receivedData) use (&$messageReceived, $loop, $message) {
            $this->assertEquals($message, $msg);
            $messageReceived = true;
            $loop->stop();
        });

        $loop->addTimer(1, function () use ($broker, $channel, $message, $data) {
            $broker->publish($channel, $message, $data);
        });

        $loop->addTimer(5, function () use ($loop) {
            $loop->stop();
        });

        $loop->run();

        $this->assertTrue($messageReceived, 'Message was not received');
    }
}
