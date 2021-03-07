<?php

namespace Ecotone\Dbal;

use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;

class DbalConnection
{
    public static function getConnection(DbalConnectionFactory|ManagerRegistryConnectionFactory $connectionFactory) : Connection
    {
        return $connectionFactory->createContext()->getDbalConnection();
    }
}