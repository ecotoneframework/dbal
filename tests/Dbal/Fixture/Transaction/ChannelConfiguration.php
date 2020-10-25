<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    #[ApplicationContext]
    public function registerCommandChannel(): array
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