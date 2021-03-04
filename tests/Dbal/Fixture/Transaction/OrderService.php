<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\Poller;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use InvalidArgumentException;

class OrderService
{
    const ORDER_TABLE = "orders";

    #[CommandHandler("order.register")]
    public function register(string $order, #[Reference(DbalConnectionFactory::class)] ManagerRegistryConnectionFactory $connectionFactory): void
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

    #[QueryHandler("order.getRegistered")]
    public function hasOrder(#[Reference(DbalConnectionFactory::class)] ManagerRegistryConnectionFactory $connectionFactory): array
    {
        $connection = $connectionFactory->createContext()->getDbalConnection();

        $isTableExists = $this->doesTableExists($connection);

        if (!$isTableExists) {
            return [];
        }

        return $connection->executeQuery(<<<SQL
    SELECT * FROM orders
SQL)->fetchFirstColumn();
    }

    private function doesTableExists(\Doctrine\DBAL\Connection $connection)
    {
        $isTableExists = $connection->executeQuery(
            <<<SQL
SELECT EXISTS (
   SELECT FROM information_schema.tables 
   WHERE  table_name   = 'orders'
   );
SQL
        )->fetchOne();

        return $isTableExists;
    }
}