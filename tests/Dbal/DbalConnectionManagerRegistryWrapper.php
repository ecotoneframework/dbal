<?php

namespace Test\Ecotone\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Enqueue\Dbal\DbalConnectionFactory;

class DbalConnectionManagerRegistryWrapper implements ManagerRegistry
{
    private $connection;

    public function __construct(DbalConnectionFactory $dbalConnectionFactory)
    {
        $this->connection = $dbalConnectionFactory->createContext()->getDbalConnection();
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