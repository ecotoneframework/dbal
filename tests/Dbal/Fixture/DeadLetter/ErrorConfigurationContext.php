<?php
declare(strict_types=1);

namespace Test\Ecotone\Dbal\Fixture\DeadLetter;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Dbal\Recoverability\DbalDeadLetterBuilder;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ErrorConfigurationContext
{
    const INPUT_CHANNEL = "inputChannel";
    const ERROR_CHANNEL = "errorChannel";
    const DEAD_LETTER_CHANNEL = "deadLetterChannel";


    #[ApplicationContext]
    public function getInputChannel()
    {
        return DbalBackedMessageChannelBuilder::create(self::INPUT_CHANNEL, "managerRegistry")
            ->withReceiveTimeout(1);
    }

    #[ApplicationContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(1),
            DbalDeadLetterBuilder::STORE_CHANNEL
        );
    }

    #[ApplicationContext]
    public function pollingConfiguration()
    {
        return PollingMetadata::create("orderService")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL);
    }

    #[ApplicationContext]
    public function dbalConfiguration()
    {
        return DbalConfiguration::createWithDefaults()
            ->withDeadLetter(true, "managerRegistry")
            ->withDefaultConnectionReferenceNames(["managerRegistry"]);
    }
}