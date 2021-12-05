<?php

namespace Ecotone\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
        throw InvalidArgumentException::create("Method not supported");
    }

    public function getManagers()
    {
        return [];
    }

    public function resetManager($name = null)
    {
        throw InvalidArgumentException::create("Method not supported");
    }

    public function getAliasNamespace($alias)
    {
        throw InvalidArgumentException::create("Method not supported");
    }

    public function getManagerNames()
    {
        throw InvalidArgumentException::create("Method not supported");
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        throw InvalidArgumentException::create("Method not supported");
    }

    public function getManagerForClass($class)
    {
        // TODO: Implement getManagerForClass() method.
    }
}