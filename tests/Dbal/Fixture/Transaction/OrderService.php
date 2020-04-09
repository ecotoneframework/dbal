<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Poller;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use InvalidArgumentException;

/**
 * Class OrderService
 * @package Test\Ecotone\Amqp\Fixture\Order
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class OrderService
{
    /**
     * @param string $order
     * @param OrderRegisteringGateway $orderRegisteringGateway
     * @CommandHandler(inputChannelName="order.register")
     */
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);

        throw new InvalidArgumentException("test");
    }

    /**
     * @ServiceActivator(
     *     endpointId="placeOrderEndpoint",
     *     inputChannelName="placeOrder",
     *     poller=@Poller(handledMessageLimit=1, executionTimeLimitInMilliseconds=1, errorChannelName="errorChannel")
     * )
     */
    public function throwExceptionOnReceive(string $order): void
    {
        throw new InvalidArgumentException("Order was not rollbacked");
    }

    /**
     * @ServiceActivator(inputChannelName="errorChannel")
     */
    public function errorConfiguration(MessagingException $exception)
    {
        throw $exception;
    }
}