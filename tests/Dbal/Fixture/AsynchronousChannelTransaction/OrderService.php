<?php


namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\MessagingException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use InvalidArgumentException;

class OrderService
{
    private array $orders = [];

    private int $callCounter = 0;

    #[CommandHandler("order.register", "orderRegister")]
    #[Asynchronous("orders")]
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);

        if ($this->callCounter === 0) {
            $this->callCounter++;
            throw new InvalidArgumentException("test");
        }

        $this->callCounter = 0;
    }

    #[Asynchronous("processOrders")]
    #[CommandHandler("placeOrder", "placeOrderEndpoint")]
    public function placeOrder(string $order) : void
    {
        $this->orders[] = $order;
    }

    #[QueryHandler("order.getRegistered")]
    public function getRegistered() : array
    {
        return $this->orders;
    }
}