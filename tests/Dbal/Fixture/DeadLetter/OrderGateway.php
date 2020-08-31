<?php

namespace Test\Ecotone\Dbal\Fixture\DeadLetter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\MessageGateway;

interface OrderGateway
{
    /**
     * @MessageGateway(requestChannel=ErrorConfigurationContext::INPUT_CHANNEL)
     */
    public function order(string $type) : void;

    /**
     * @MessageGateway(requestChannel="getOrderAmount")
     */
    public function getOrderAmount() : int;
}