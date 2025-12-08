<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Support\Facades\Log;
use React\EventLoop\LoopInterface;

class RedisMessageBroker implements MessageBrokerInterface
{
    private LoopInterface $loop;
    private ?Client $publishClient = null;
    private ?Client $subscribeClient = null;
    private string $host;
    private int $port;
    private array $channelCallbacks = [];
    private bool $messageHandlerAttached = false;

    public function __construct(LoopInterface $loop, string $host = '127.0.0.1', int $port = 6379)
    {
        $this->loop = $loop;
        $this->host = $host;
        $this->port = $port;
    }

    private function connectPublish(): void
    {
        if ($this->publishClient !== null) {
            return;
        }

        $factory = new Factory($this->loop);
        $factory->createClient("redis://{$this->host}:{$this->port}")->then(function (Client $client) {
            $this->publishClient = $client;
            echo "Connected to Redis (publish)\n";
        });
    }

    private function connectSubscribe(): void
    {
        if ($this->subscribeClient !== null) {
            return;
        }

        $factory = new Factory($this->loop);
        $factory->createClient("redis://{$this->host}:{$this->port}")->then(function (Client $client) {
            $this->subscribeClient = $client;
            $this->attachMessageHandler();
            echo "Connected to Redis (subscribe)\n";
        });
    }

    private function attachMessageHandler(): void
    {
        if ($this->messageHandlerAttached || $this->subscribeClient === null) {
            return;
        }

        $this->subscribeClient->on('message', function ($ch, $message) {
            if (!isset($this->channelCallbacks[$ch])) {
                return;
            }

            $data = json_decode($message, true);
            foreach ($this->channelCallbacks[$ch] as $callback) {
                $callback($data['message'] ?? '', $data['data'] ?? []);
            }
        });

        $this->messageHandlerAttached = true;
    }

    public function publish(string $channel, string $message, array $data = []): void
    {
        if ($this->publishClient === null) {
            $factory = new Factory($this->loop);
            // Дожидаемся подключения И публикации
            $promise = $factory->createClient("redis://{$this->host}:{$this->port}")
                ->then(function (Client $client) use ($channel, $message, $data) {
                    $this->publishClient = $client;
                    return $this->doPublish($channel, $message, $data);
                });

            // Теперь await блокирует весь HTTP-запрос до завершения
            \React\Async\await($promise);
        } else {
            // Уже подключены, просто публикуем и ждём
            $promise = $this->doPublish($channel, $message, $data);
            \React\Async\await($promise);
        }
    }

    private function doPublish(string $channel, string $message, array $data): mixed
    {
        $payload = json_encode([
            'message' => $message,
            'data' => $data,
        ]);

        return $this->publishClient->publish($channel, $payload);

    }

    public function subscribe(string $channel, callable $callback): void
    {
        $this->channelCallbacks[$channel][] = $callback;

        if ($this->subscribeClient === null) {
            $factory = new Factory($this->loop);
            $factory->createClient("redis://{$this->host}:{$this->port}")->then(function (Client $client) use ($channel) {
                $this->subscribeClient = $client;
                $this->attachMessageHandler();
                $this->subscribeClient->subscribe($channel);
                echo "Subscribed to channel: {$channel}\n";
                Log::info("Subscribed to channel: {$channel}\n");
            });
        } else {
            $this->subscribeClient->subscribe($channel);
            echo "Subscribed to   channel: {$channel}\n";
            Log::info("Subscribed   to channel: {$channel}\n");
        }
    }
}
