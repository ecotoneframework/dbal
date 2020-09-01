<?php

namespace Test\Ecotone\Dbal\Recoverability;

use Ecotone\Dbal\Recoverability\DbalDeadLetter;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Dbal\DbalMessagingTest;

class DbalDeadLetterTest extends DbalMessagingTest
{
    /**
     * @before
     */
    public function before() : void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->beginTransaction();
    }

    /**
     * @after
     */
    public function after(): void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->rollBack();
    }

    public function test_retrieving_error_message_details()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $errorMessage = MessageBuilder::withPayload("")->build();
        $dbalDeadLetter->store($errorMessage);

        $this->assertEquals(
            $errorMessage,
            $dbalDeadLetter->show($errorMessage->getHeaders()->getMessageId())
        );
    }

    public function test_listing_error_messages()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $secondErrorMessage = MessageBuilder::withPayload("error1")
                                ->setMultipleHeaders([
                                    ErrorContext::EXCEPTION_STACKTRACE => "#12",
                                    ErrorContext::EXCEPTION_LINE => 120,
                                    ErrorContext::EXCEPTION_FILE => "dbalDeadLetter.php",
                                    ErrorContext::EXCEPTION_CODE => 1,
                                    ErrorContext::EXCEPTION_MESSAGE => 1,
                                ])
                                ->build();
        $dbalDeadLetter->store(MessageBuilder::withPayload("error2")->build());
        $dbalDeadLetter->store($secondErrorMessage);

        $this->assertEquals(
            [ErrorContext::fromHeaders($secondErrorMessage->getHeaders()->headers())],
            $dbalDeadLetter->list(1, 1)
        );
    }

    public function test_deleting_error_message()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $message = MessageBuilder::withPayload("error2")->build();
        $dbalDeadLetter->store($message);
        $dbalDeadLetter->delete($message->getHeaders()->getMessageId());

        $this->assertEquals(
            [],
            $dbalDeadLetter->list(1, 0)
        );
    }
}