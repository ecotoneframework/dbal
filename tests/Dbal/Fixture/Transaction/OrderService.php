<?php


namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use InvalidArgumentException;

class OrderService
{
    #[CommandHandler("order.register")]
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);

        throw new InvalidArgumentException("test");
    }

    #[ServiceActivator("placeOrder", "placeOrderEndpoint")]
    public function throwExceptionOnReceive(string $order): void
    {
        throw new InvalidArgumentException("Order was not rollbacked");
    }

    #[ServiceActivator("errorChannel")]
    public function errorConfiguration(MessagingException $exception)
    {
        throw $exception;
    }
}