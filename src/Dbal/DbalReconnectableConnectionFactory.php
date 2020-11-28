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
    private ConnectionFactory|ManagerRegistryConnectionFactory $connectionFactory;

    public function __construct(ConnectionFactory $dbalConnectionFactory)
    {
        $this->connectionFactory = $dbalConnectionFactory;
    }

    public static function getManagerRegistryAndConnectionName(ManagerRegistryConnectionFactory $connectionFactory): array
    {
        $reflectionClass   = new ReflectionClass($connectionFactory);

        $registry = $reflectionClass->getProperty("registry");
        $registry->setAccessible(true);
        $config = $reflectionClass->getProperty("config");
        $config->setAccessible(true);

        $connectionName = $config->getValue($connectionFactory)["connection_name"];
        /** @var ManagerRegistry $registry */
        $registry = $registry->getValue($connectionFactory);

        return array($registry, $connectionName);
    }

    public function createContext(): Context
    {
        $this->reconnect();

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
        $connectionFactory = $this->connectionFactory;
        if ($connectionFactory instanceof ManagerRegistryConnectionFactory) {
            list($registry, $connectionName) = self::getManagerRegistryAndConnectionName($connectionFactory);
            /** @var Connection $connection */
            $connection = $registry->getConnection($connectionName);

            $connection->close();
            $connection->connect();
        }else {
            $reflectionClass   = new ReflectionClass($connectionFactory);
            $connectionProperty = $reflectionClass->getProperty("connection");
            $connectionProperty->setAccessible(true);
            /** @var Connection $connection */
            $connection = $connectionProperty->getValue($connectionFactory);
            if ($connection) {
                $connection->close();
                $connection->connect();
                $connectionProperty->setValue($connectionFactory, null);
            }
        }
    }
}