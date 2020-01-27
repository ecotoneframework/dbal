<?php


namespace Ecotone\Dbal;


use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use ReflectionClass;

class DbalReconnectableConnectionFactory implements ReconnectableConnectionFactory
{
    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    public function __construct(ConnectionFactory $dbalConnectionFactory)
    {
        $this->connectionFactory = $dbalConnectionFactory;
    }

    public function createContext(): Context
    {
        return $this->connectionFactory->createContext();
    }

    /**
     * @param Context|null|DbalContext $context
     * @return bool
     */
    public function isDisconnected(?Context $context): bool
    {
        if (!$context) {
            return false;
        }

        return !$context->getDbalConnection()->isConnected();
    }

    public function reconnect(): void
    {
        $reflectionClass = new ReflectionClass($this->connectionFactory);

        $connectionProperty = $reflectionClass->getProperty("connection");
        $connectionProperty->setAccessible(true);
        $connectionProperty->setValue($this->connectionFactory, null);
    }
}