<?php

namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Annotation\MessageGateway;

interface OrderRegisteringGateway
{
    /**
     * @MessageGateway(requestChannel="placeOrder")
     */
    public function place(string $order): void;
}