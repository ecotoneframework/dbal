<?php


namespace Test\Ecotone\Dbal;


use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ramsey\Uuid\Uuid;

class DbalBackedMessageChannelTest extends DbalMessagingTest
{
    public function test_sending_and_receiving_via_channel()
    {
        $channelName = Uuid::uuid4()->toString();

        /** @var PollableChannel $messageChannel */
        $messageChannel = DbalBackedMessageChannelBuilder::create($channelName)
                            ->withReceiveTimeout(1)
                            ->build($this->getReferenceSearchServiceWithConnection());

        $payload = "some";
        $headerName = "token";
        $messageChannel->send(
            MessageBuilder::withPayload($payload)
                ->setHeader($headerName, 123)
                ->build()
        );

        $receivedMessage = $messageChannel->receive();

        $this->assertNotNull($receivedMessage, "Not received message");
        $this->assertEquals($payload, $receivedMessage->getPayload(), "Payload of received is different that sent one");
        $this->assertEquals(123, $receivedMessage->getHeaders()->get($headerName));
    }
}