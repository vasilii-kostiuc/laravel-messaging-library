<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Redis;
use React\EventLoop\Loop;
use Tests\TestCase;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\RedisMessageBroker;

class RedisMessageBrokerTest extends TestCase
{
    public function test_publish(): void
    {
        $loop = Loop::get();
        $broker = new RedisMessageBroker($loop, 'redis', 6379);
        $channel = 'test_channel';
        $message = 'test_message';
        $data = ['key' => 'value'];

        $messageReceived = false;

        // Подписываемся на канал
        $broker->subscribe($channel, function ($msg, $receivedData) use (&$messageReceived, $loop, $message) {
            $this->assertEquals($message, $msg);
            $messageReceived = true;
            // Останавливаем loop после получения сообщения
            $loop->stop();
        });

        // Публикуем сообщение через 1 секунду (чтобы подписка успела установиться)
        $loop->addTimer(1, function () use ($broker, $channel, $message, $data) {
            $broker->publish($channel, $message, $data);
        });

        // Останавливаем loop через 5 секунд в любом случае (таймаут теста)
        $loop->addTimer(5, function () use ($loop) {
            $loop->stop();
        });

        $loop->run();

        $this->assertTrue($messageReceived, 'Message was not received');
    }
}
