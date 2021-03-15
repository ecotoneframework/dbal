<?php

namespace Test\Ecotone\Dbal\Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Dbal\Recoverability\DbalDeadLetter;
use Ecotone\Dbal\Recoverability\DeadLetterGateway;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use Test\Ecotone\Dbal\DbalConnectionManagerRegistryWrapper;
use Test\Ecotone\Dbal\Fixture\DeadLetter\OrderGateway;
use Test\Ecotone\Dbal\Fixture\Transaction\OrderService;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\OrderErrorHandler;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private static $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        switch ($namespace) {
            case "Test\Ecotone\Dbal\Fixture\Transaction": {
                $objects = [
                    new OrderService()
                ];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction": {
                $objects = [
                    new \Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction\OrderService()
                ];
                break;
            }
            case "Test\Ecotone\Dbal\Fixture\DeadLetter": {
                $objects = [
                    new \Test\Ecotone\Dbal\Fixture\DeadLetter\OrderService()
                ];
                break;
            }
            default: {
                throw new \InvalidArgumentException("Namespace {$namespace} not yet implemented");
            }
        }

        $managerRegistryConnectionFactory = new ManagerRegistryConnectionFactory(new DbalConnectionManagerRegistryWrapper(new DbalConnectionFactory(["dsn" => 'pgsql://ecotone:secret@database:5432/ecotone'])));
        $connection = $managerRegistryConnectionFactory->createContext()->getDbalConnection();
        $isTableExists = $connection->executeQuery(
            <<<SQL
SELECT EXISTS (
   SELECT FROM information_schema.tables 
   WHERE  table_name   = 'enqueue'
   );
SQL
        )->fetchOne();
        if ($isTableExists) {
            $this->deleteFromTableExists("enqueue", $connection);
            $this->deleteFromTableExists(OrderService::ORDER_TABLE, $connection);
            $this->deleteFromTableExists(DbalDeadLetter::DEFAULT_DEAD_LETTER_TABLE, $connection);
        }

        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withNamespaces([$namespace])
            ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString());
        MessagingSystemConfiguration::cleanCache($serviceConfiguration->getCacheDirectoryPath());
        self::$messagingSystem            = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects(array_merge($objects, ["managerRegistry" => $managerRegistryConnectionFactory, DbalConnectionFactory::class => $managerRegistryConnectionFactory])),
            $serviceConfiguration,
            [],
            true
        );
    }

    private function deleteFromTableExists(string $tableName, \Doctrine\DBAL\Connection $connection) : void
    {
        $doesExists = $connection->executeQuery(
            <<<SQL
SELECT EXISTS (
   SELECT FROM information_schema.tables 
   WHERE  table_name   = :tableName
   );
SQL, ["tableName" => $tableName]
        )->fetchOne();

        if ($doesExists) {
            $connection->executeUpdate("DELETE FROM " . $tableName);
        }
    }

    /**
     * @When I active receiver :receiverName
     * @param string $receiverName
     */
    public function iActiveReceiver(string $receiverName)
    {
        self::$messagingSystem->run($receiverName);
    }

    /**
     * @Then there should be nothing on the order list
     */
    public function thereShouldBeNothingOnTheOrderList()
    {
        $this->assertEquals(
            [],
            $this->getQueryBus()->sendWithRouting("order.getOrders", [])
        );
    }

    private function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
    }

    private function getQueryBus() : QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }

    /**
     * @When I transactionally order :order
     */
    public function iTransactionallyOrder(string $order)
    {
        /** @var CommandBus $commandBus */
        $commandBus = self::$messagingSystem->getGatewayByName(CommandBus::class);

        try {
            $commandBus->sendWithRouting("order.register", $order);
        }catch (\InvalidArgumentException $e) {}
    }

    /**
     * @When I order :order
     */
    public function iOrder(string $order)
    {
        /** @var OrderGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $gateway->order($order);
    }

    /**
     * @When I call pollable endpoint :consumerId
     */
    public function iCallPollableEndpoint(string $consumerId)
    {
        self::$messagingSystem->run($consumerId);
    }

    /**
     * @Then there should be :amount orders
     */
    public function thereShouldBeOrders(int $amount)
    {
        /** @var OrderGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(OrderGateway::class);

        $this->assertEquals(
            $amount,
            $gateway->getOrderAmount()
        );
    }

    /**
     * @Then there should :amount error message in dead letter
     */
    public function thereShouldErrorMessageInDeadLetter(int $amount)
    {
        /** @var DeadLetterGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(DeadLetterGateway::class);

        $this->assertEquals(
            $amount,
            count($gateway->list(100,0))
        );
    }

    /**
     * @When all error messages are replied
     */
    public function whenAllErrorMessagesAreReplied()
    {
        /** @var DeadLetterGateway $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(DeadLetterGateway::class);

        $gateway->replyAll();
    }

    /**
     * @Then there should :amount registered orders
     */
    public function thereShouldRegisteredOrders($amount)
    {
        $this->assertEquals(
            $amount,
            count($this->getQueryBus()->sendWithRouting("order.getRegistered", []))
        );
    }
}
