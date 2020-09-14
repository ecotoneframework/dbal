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

    /**
     * @Asynchronous("orders")
     * @CommandHandler(endpointId="orderRegister", inputChannelName="order.register")
     */
    public function register(string $order, OrderRegisteringGateway $orderRegisteringGateway): void
    {
        $orderRegisteringGateway->place($order);

        if ($this->callCounter === 0) {
            $this->callCounter++;
            throw new InvalidArgumentException("test");
        }

        $this->callCounter = 0;
    }

    /**
     * @Asynchronous("processOrders")
     * @CommandHandler(endpointId="placeOrderEndpoint", inputChannelName="placeOrder")
     */
    public function placeOrder(string $order) : void
    {
        $this->orders[] = $order;
    }

    /**
     * @QueryHandler(inputChannelName="order.getRegistered")
     */
    public function getRegistered() : array
    {
        return $this->orders;
    }
}