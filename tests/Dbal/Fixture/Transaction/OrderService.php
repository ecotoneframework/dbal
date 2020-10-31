<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Annotation\Parameter\Reference;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use InvalidArgumentException;

class OrderService
{
    /**
     * @CommandHandler("order.register", parameterConverters={@Reference(parameterName="connectionFactory", referenceName="Enqueue\Dbal\DbalConnectionFactory")})
     */
    public function register(string $order, ManagerRegistryConnectionFactory $connectionFactory): void
    {
        $connection = $connectionFactory->createContext()->getDbalConnection();

        $connection->executeStatement(<<<SQL
    CREATE TABLE IF NOT EXISTS orders (id VARCHAR(255) PRIMARY KEY)
SQL);
        $connection->executeStatement(<<<SQL
    INSERT INTO orders VALUES (:order)
SQL, ["order" => $order]);

        throw new InvalidArgumentException("test");
    }

    /**
     * @QueryHandler("order.getRegistered", parameterConverters={@Reference(parameterName="connectionFactory", referenceName="Enqueue\Dbal\DbalConnectionFactory")})
     */
    public function hasOrder(ManagerRegistryConnectionFactory $connectionFactory): array
    {
        $connection = $connectionFactory->createContext()->getDbalConnection();

        $isTableExists = $connection->executeQuery(
            <<<SQL
SELECT EXISTS (
   SELECT FROM information_schema.tables 
   WHERE  table_name   = 'orders'
   );
SQL
        )->fetchOne();

        if (!$isTableExists) {
            return [];
        }

        return $connection->executeQuery(<<<SQL
    SELECT * FROM orders
SQL)->fetchFirstColumn();
    }
}