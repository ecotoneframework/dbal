<?php

namespace Ecotone\Dbal\Recoverability;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

interface DeadLetterGateway
{
    /**
     * @return ErrorContext[]
     * @MessageGateway(
     *     requestChannel=DbalDeadLetterBuilder::LIST_CHANNEL,
     *     parameterConverters={
     *          @Header(parameterName="limit", headerName=DbalDeadLetterBuilder::LIMIT_HEADER),
     *          @Header(parameterName="offset", headerName=DbalDeadLetterBuilder::OFFSET_HEADER)
     *     }
     * )
     */
    public function list(int $limit, int $offset) : array;

    /**
     * @throws InvalidArgumentException on not found
     *
     * @MessageGateway(requestChannel=DbalDeadLetterBuilder::DETAILS_CHANNEL)
     */
    public function getDetails(string $messageId) : Message;

    /**
     * @MessageGateway(requestChannel=DbalDeadLetterBuilder::REPLY_CHANNEL)
     */
    public function reply(string $messageId) : void;

    /**
     * @MessageGateway(requestChannel=DbalDeadLetterBuilder::REPLY_ALL_CHANNEL)
     */
    public function replyAll() : void;

    /**
     * @MessageGateway(requestChannel=DbalDeadLetterBuilder::DELETE_CHANNEL)
     */
    public function delete(string $messageId) : void;
}