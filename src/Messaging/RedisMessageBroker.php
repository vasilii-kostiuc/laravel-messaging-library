<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
            info(__CLASS__ . " Received message on channel in RedisMessageBroker : {$ch}, message: {$message}");
            info(__CLASS__ . " Channel callbacks: " . json_encode($this->channelCallbacks));

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
        $this->doPublish($channel, $message, $data);
        return;
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

        return Redis::publish($channel, $payload);
        //return $this->publishClient->publish($channel, $payload);

    }

    private array $pendingSubscriptions = [];

    public function subscribe(string $channel, callable $callback): void
    {
        $this->channelCallbacks[$channel][] = $callback;

        if ($this->subscribeClient === null) {
            $this->pendingSubscriptions[] = $channel;

            // Создаём клиента только один раз
            if (count($this->pendingSubscriptions) === 1) {
                $factory = new Factory($this->loop);
                $factory->createClient("redis://{$this->host}:{$this->port}")->then(function (Client $client) {
                    $this->subscribeClient = $client;
                    $this->attachMessageHandler();
                    foreach ($this->pendingSubscriptions as $ch) {
                        $this->subscribeClient->subscribe($ch);
                        Log::info(__METHOD__ . " Subscribed to channel: {$ch}");
                    }
                    $this->pendingSubscriptions = [];
                });
            }
        } else {
            $this->subscribeClient->subscribe($channel);
            Log::info(__METHOD__ . " Subscribed to channel: {$channel}");
        }
    }
}
