<?php

namespace Test\Ecotone\Dbal\Fixture\DeadLetter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class OrderService
{
    private int $callCount = 0;

    private int $placedOrders = 0;

    /**
     * @ServiceActivator(
     *     endpointId="orderService",
     *     inputChannelName=ErrorConfigurationContext::INPUT_CHANNEL
     * )
     */
    public function order(string $orderName) : void
    {
        $this->callCount += 1;

        if ($this->callCount > 2) {
            $this->placedOrders++;

            return;
        }

        throw new \InvalidArgumentException("exception");
    }

    /**
     * @ServiceActivator(inputChannelName="getOrderAmount")
     */
    public function getOrder() : int
    {
        return $this->placedOrders;
    }
}