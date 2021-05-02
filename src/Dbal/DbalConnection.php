<?php

namespace Ecotone\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;

class DbalConnection implements ManagerRegistry
{
    private function __construct(private Connection $connection) {}

    public static function fromConnectionFactory(DbalConnectionFactory $dbalConnectionFactory): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory(new self($dbalConnectionFactory->createContext()->getDbalConnection()));
    }

    public static function create(Connection $connection): ManagerRegistryConnectionFactory
    {
        return new ManagerRegistryConnectionFactory(new self($connection));
    }

    public function getDefaultConnectionName()
    {
        return "default";
    }

    public function getConnection($name = null)
    {
        return $this->connection;
    }

    public function getConnections()
    {
        return [$this->connection];
    }

    public function getConnectionNames()
    {
        return ["default"];
    }

    public function getDefaultManagerName()
    {
        return "default";
    }

    public function getManager($name = null)
    {
        // TODO: Implement getManager() method.
    }

    public function getManagers()
    {
        return [];
    }

    public function resetManager($name = null)
    {
        // TODO: Implement resetManager() method.
    }

    public function getAliasNamespace($alias)
    {
        // TODO: Implement getAliasNamespace() method.
    }

    public function getManagerNames()
    {
        // TODO: Implement getManagerNames() method.
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        // TODO: Implement getRepository() method.
    }

    public function getManagerForClass($class)
    {
        // TODO: Implement getManagerForClass() method.
    }
}