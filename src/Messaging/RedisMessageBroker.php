<?php

namespace VasiliiKostiuc\LaravelMessagingLibrary\Messaging;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;

class RedisMessageBroker implements MessageBrokerInterface
{
    private LoopInterface $loop;

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

    private function connectSubscribe(): void
    {
        if ($this->subscribeClient !== null) {
            return;
        }

        $factory = new Factory($this->loop);
        $factory->createClient("redis://{$this->host}:{$this->port}")->then(function (Client $client) {
            $this->subscribeClient = $client;
            $this->attachMessageHandler();
        });
    }

    private function attachMessageHandler(): void
    {
        if ($this->messageHandlerAttached || $this->subscribeClient === null) {
            return;
        }

        $this->subscribeClient->on('message', function ($ch, $message) {
            if (! isset($this->channelCallbacks[$ch])) {
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
    }

    private function doPublish(string $channel, string $message, array $data): void
    {
        $payload = json_encode([
            'message' => $message,
            'data' => $data,
        ]);

        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => $this->host,
            'port' => $this->port,
        ]);

        $redis->publish($channel, $payload);
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
                    }
                    $this->pendingSubscriptions = [];
                });
            }
        } else {
            $this->subscribeClient->subscribe($channel);
        }
    }
}
