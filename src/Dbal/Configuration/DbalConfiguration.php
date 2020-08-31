<?php

namespace Ecotone\Dbal\Configuration;

use Ecotone\Messaging\Config\ConfigurationException;
use Enqueue\Dbal\DbalConnectionFactory;

class DbalConfiguration
{
    const DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS = true;
    const DEFAULT_TRANSACTION_ON_COMMAND_BUS = true;
    const DEFAULT_DEDUPLICATION_ENABLED = false;

    private bool $defaultTransactionOnAsynchronousEndpoints = self::DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS;

    private bool $defaultTransactionOnCommandBus = self::DEFAULT_TRANSACTION_ON_COMMAND_BUS;

    private array $defaultConnectionReferenceNames = [DbalConnectionFactory::class];

    private bool $deduplicatedEnabled = self::DEFAULT_DEDUPLICATION_ENABLED;

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
        $self = clone $this;
        $self->defaultTransactionOnAsynchronousEndpoints = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnCommandBus(bool $isTransactionEnabled) : self
    {
        $self = clone $this;
        $self->defaultTransactionOnCommandBus = $isTransactionEnabled;

        return $self;
    }

    public function withDefaultConnectionReferenceNames(array $connectionReferenceNames = [DbalConnectionFactory::class]) : self
    {
        $self = clone $this;
        $self->defaultConnectionReferenceNames = $connectionReferenceNames;

        return $self;
    }

    public function withDeduplication(bool $isDeduplicatedEnabled) : self
    {
        $self = clone $this;
        $self->deduplicatedEnabled = $isDeduplicatedEnabled;

        return $self;
    }

    public function isDeduplicatedEnabled(): bool
    {
        return $this->deduplicatedEnabled;
    }

    /**
     * @return bool
     */
    public function isDefaultTransactionOnAsynchronousEndpoints(): bool
    {
        return $this->defaultTransactionOnAsynchronousEndpoints;
    }

    /**
     * @return bool
     */
    public function isDefaultTransactionOnCommandBus(): bool
    {
        return $this->defaultTransactionOnCommandBus;
    }

    /**
     * @return array
     */
    public function getDefaultConnectionReferenceNames(): array
    {
        return $this->defaultConnectionReferenceNames;
    }
}