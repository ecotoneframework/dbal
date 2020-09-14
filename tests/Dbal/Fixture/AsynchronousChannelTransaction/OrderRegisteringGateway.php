<?php

namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Ecotone\Messaging\Annotation\MessageGateway;

interface OrderRegisteringGateway
{
    /**
     * @MessageGateway(requestChannel="placeOrder")
     */
    public function place(string $order): void;
}