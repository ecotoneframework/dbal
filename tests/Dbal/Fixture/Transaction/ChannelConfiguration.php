<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class ChannelConfiguration
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ChannelConfiguration
{
    /**
     * @Extension()
     */
    public function registerCommandChannel() : array
    {
        return [
            DbalBackedMessageChannelBuilder::create("placeOrder", "managerRegistry")
                ->withReceiveTimeout(1),
            PollingMetadata::create("placeOrderEndpoint")
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setErrorChannelName("errorChannel"),
            DbalConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true)
                ->withDefaultConnectionReferenceNames(["managerRegistry"])
        ];
    }

}