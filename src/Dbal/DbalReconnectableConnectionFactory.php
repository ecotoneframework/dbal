<?php


namespace Ecotone\Dbal;


use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Enqueue\ReconnectableConnectionFactory;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
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

    public function getConnectionInstanceId(): int
    {
        return spl_object_id($this->connectionFactory);
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

        return !$context->getDbalConnection()->isConnected()  || !$context->getDbalConnection()->ping();
    }

    public function reconnect(): void
    {
        $reflectionClass = new ReflectionClass($this->connectionFactory);
        if ($this->connectionFactory instanceof ManagerRegistryConnectionFactory) {
            $registry = $reflectionClass->getProperty("registry");
            $registry->setAccessible(true);
            $config = $reflectionClass->getProperty("config");
            $config->setAccessible(true);

            $connectionName = $config->getValue($this->connectionFactory)["connection_name"];
            /** @var ManagerRegistry $registry */
            $registry = $registry->getValue($this->connectionFactory);
            /** @var Connection $connection */
            $connection = $registry->getConnection($connectionName);

            $connection->close();
            $connection->connect();
        }else {
            $connectionProperty = $reflectionClass->getProperty("connection");
            $connectionProperty->setAccessible(true);
            $connectionProperty->setValue($this->connectionFactory, null);
        }
    }
}