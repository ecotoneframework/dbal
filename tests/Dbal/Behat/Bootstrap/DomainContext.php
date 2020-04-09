<?php

namespace Test\Ecotone\Dbal\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ApplicationConfiguration;
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
     * @param string $namespace
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws InvalidArgumentException
     * @throws ReflectionException
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
            default: {
                throw new \InvalidArgumentException("Namespace {$namespace} not yet implemented");
            }
        }

        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects(array_merge($objects, ["managerRegistry" => new ManagerRegistryConnectionFactory(new DbalConnectionManagerRegistryWrapper(new DbalConnectionFactory(["dsn" => 'pgsql://ecotone:secret@database:5432/ecotone'])))])),
            ApplicationConfiguration::createWithDefaults()
                ->withNamespaces([$namespace])
                ->withCacheDirectoryPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . Uuid::uuid4()->toString())
        );
    }

    /**
     * @When I active receiver :receiverName
     * @param string $receiverName
     */
    public function iActiveReceiver(string $receiverName)
    {
        self::$messagingSystem->runSeparatelyRunningEndpointBy($receiverName);
    }

    /**
     * @Then there should be nothing on the order list
     */
    public function thereShouldBeNothingOnTheOrderList()
    {
        $this->assertEquals(
            [],
            $this->getQueryBus()->convertAndSend("order.getOrders", MediaType::APPLICATION_X_PHP, [])
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
            $commandBus->convertAndSend("order.register", MediaType::APPLICATION_X_PHP, $order);
        }catch (\InvalidArgumentException $e) {}
    }
}
