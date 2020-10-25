<?php

namespace Test\Ecotone\Dbal\Fixture\Transaction;

use Ecotone\Messaging\Annotation\MessageGateway;

interface OrderRegisteringGateway
{
    #[MessageGateway("placeOrder")]
    public function place(string $order): void;
}