<?php

namespace Ecotone\Dbal\Configuration;

use Ecotone\Messaging\Config\ConfigurationException;
use Enqueue\Dbal\DbalConnectionFactory;

class DbalConfiguration
{
    const DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS = true;
    const DEFAULT_TRANSACTION_ON_COMMAND_BUS = true;
    const DEFAULT_TRANSACTION_ON_CONSOLE_COMMANDS = true;
    const DEFAULT_CLEAR_OBJECT_MANAGER_ON_ASYNCHRONOUS_ENDPOINTS = true;
    const DEFAULT_DEDUPLICATION_ENABLED = false;
    const DEFAULT_DEAD_LETTER_ENABLED = false;

    private bool $transactionOnAsynchronousEndpoints = self::DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS;
    private bool $transactionOnCommandBus = self::DEFAULT_TRANSACTION_ON_COMMAND_BUS;
    private bool $transactionOnConsoleCommands = self::DEFAULT_TRANSACTION_ON_CONSOLE_COMMANDS;
    private bool $clearObjectManagerOnAsynchronousEndpoints = self::DEFAULT_CLEAR_OBJECT_MANAGER_ON_ASYNCHRONOUS_ENDPOINTS;
    private array $defaultConnectionReferenceNames = [DbalConnectionFactory::class];

    private bool $deduplicatedEnabled = self::DEFAULT_DEDUPLICATION_ENABLED;
    private bool $deadLetterEnabled = self::DEFAULT_DEAD_LETTER_ENABLED;

    private ?string $deduplicationConnectionReference = null;
    private ?string $deadLetterConnectionReference = null;

    private function __construct()
    {
    }

    public static function createWithDefaults() : self
    {
        return new self();
    }

    public function getDeduplicationConnectionReference(): string
    {
        return $this->getMainConnectionOrDefault($this->deduplicationConnectionReference, "deduplication");
    }

    public function getDeadLetterConnectionReference(): string
    {
        return $this->getMainConnectionOrDefault($this->deadLetterConnectionReference, "dead letter");
    }

    private function getMainConnectionOrDefault(?string $connectionReferenceName, string $type) : string
    {
        if ($connectionReferenceName) {
            return $connectionReferenceName;
        }

        if (empty($this->defaultConnectionReferenceNames)) {
            return DbalConnectionFactory::class;
        }

        if (count($this->defaultConnectionReferenceNames) !== 1) {
            throw ConfigurationException::create("Specify exact connection for {$type}. Got: " . implode(",", $this->defaultConnectionReferenceNames));
        }

        return $this->defaultConnectionReferenceNames[0];
    }

    public function withTransactionOnAsynchronousEndpoints(bool $isTransactionEnabled) : self
    {
        $self                                     = clone $this;
        $self->transactionOnAsynchronousEndpoints = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnCommandBus(bool $isTransactionEnabled) : self
    {
        $self                          = clone $this;
        $self->transactionOnCommandBus = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnConsoleCommands(bool $isTransactionEnabled) : self
    {
        $self                          = clone $this;
        $self->transactionOnConsoleCommands = $isTransactionEnabled;

        return $self;
    }

    public function withCleanObjectManagerOnAsynchronousEndpoints(bool $isTransactionEnabled) : self
    {
        $self                                     = clone $this;
        $self->clearObjectManagerOnAsynchronousEndpoints = $isTransactionEnabled;

        return $self;
    }

    public function withDefaultConnectionReferenceNames(array $connectionReferenceNames = [DbalConnectionFactory::class]) : self
    {
        $self = clone $this;
        $self->defaultConnectionReferenceNames = $connectionReferenceNames;

        return $self;
    }

    public function withDeduplication(bool $isDeduplicatedEnabled, string $connectionReference = DbalConnectionFactory::class) : self
    {
        $self = clone $this;
        $self->deduplicatedEnabled = $isDeduplicatedEnabled;
        $self->deduplicationConnectionReference = $connectionReference;

        return $self;
    }

    public function withDeadLetter(bool $isDeadLetterEnabled, string $connectionReference = DbalConnectionFactory::class) : self
    {
        $self = clone $this;
        $self->deadLetterEnabled = $isDeadLetterEnabled;
        $self->deadLetterConnectionReference = $connectionReference;

        return $self;
    }

    public function isDeduplicatedEnabled(): bool
    {
        return $this->deduplicatedEnabled;
    }

    public function isDeadLetterEnabled(): bool
    {
        return $this->deadLetterEnabled;
    }

    public function isTransactionOnAsynchronousEndpoints(): bool
    {
        return $this->transactionOnAsynchronousEndpoints;
    }

    public function isTransactionOnCommandBus(): bool
    {
        return $this->transactionOnCommandBus;
    }

    public function isTransactionOnConsoleCommands(): bool
    {
        return $this->transactionOnConsoleCommands;
    }

    public function getDefaultConnectionReferenceNames(): array
    {
        return $this->defaultConnectionReferenceNames;
    }

    public function isClearObjectManagerOnAsynchronousEndpoints(): bool
    {
        return $this->clearObjectManagerOnAsynchronousEndpoints;
    }
}