<?php

namespace Ecotone\Dbal\Configuration;

use Enqueue\Dbal\DbalConnectionFactory;

class DbalConfiguration
{
    const DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS = false;
    const DEFAULT_TRANSACTION_ON_COMMAND_BUS = false;
    const DEFAULT_DEDUPLICATION_ENABLED = false;

    private $defaultTransactionOnAsynchronousEndpoints = self::DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS;

    private $defaultTransactionOnCommandBus = self::DEFAULT_TRANSACTION_ON_COMMAND_BUS;

    private $deduplicatedEnabled = self::DEFAULT_DEDUPLICATION_ENABLED;

    /**
     * @var array
     */
    private $defaultConnectionReferenceNames = [];

    private function __construct()
    {
    }

    public static function createWithDefaults() : self
    {
        return new self();
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