<?php

namespace App\Services;

use Illuminate\Log\LogManager;

class LogService
{
    protected array $context = [];

    public function __construct(
        protected ?LogManager $logManager = null,
        protected string $channel = 'stack'
    ) {}

    public function channel(string $channel): self
    {
        $new = clone $this;
        $new->channel = $channel;
        return $new;
    }

    public function withContext(array $context): self
    {
        $new = clone $this;
        $new->context = array_merge($this->context, $context);
        return $new;
    }

    protected function logger()
    {
        return $this->logManager?->channel($this->channel);
    }

    public function info(string $message, array $data = []): void
    {
        $this->logger()?->info($message, array_merge($this->context, $data));
    }

    public function error(string $message, array $data = []): void
    {
        $this->logger()?->error($message, array_merge($this->context, $data));
    }

    public function warning(string $message, array $data = []): void
    {
        $this->logger()?->warning($message, array_merge($this->context, $data));
    }
}
