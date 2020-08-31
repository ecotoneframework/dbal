<?php


namespace Ecotone\Dbal\Recoverability;


use Ecotone\Messaging\Annotation\OneTimeCommand;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\MessageHeaders;

class DbalDeadLetterOneTimeCommand
{
    const PAGE_LIMIT = 20;

    /**
     * @OneTimeCommand("ecotone:dbal:deadletter:list")
     */
    public function listMessages(DeadLetterGateway $deadLetterGateway, int $page = 0) : OneTimeCommandResultSet
    {
        $limit = self::PAGE_LIMIT;
        $offset = $page * self::PAGE_LIMIT;

        return OneTimeCommandResultSet::create(
            ["Message Id", "Failed At", "Stacktrace"],
            array_map(function(ErrorContext $errorContext) {
                return [
                    $errorContext->getMessageId(),
                    $this->convertTimestampToReadableFormat($errorContext->getFailedTimestamp(), false),
                    $this->getReadableStacktrace($errorContext->getStackTrace())
                ];
            }, $deadLetterGateway->list($limit, $offset))
        );
    }

    /**
     * @OneTimeCommand("ecotone:dbal:deadletter:show")
     */
    public function getMessageDetails(DeadLetterGateway $deadLetterGateway, string $messageId, bool $fullDetails = false) : OneTimeCommandResultSet
    {
        $message = $deadLetterGateway->getDetails($messageId);

        return OneTimeCommandResultSet::create(
            [],
            [
                ["Message Id", $message->getHeaders()->getMessageId()],
                ["Failed At", $this->convertTimestampToReadableFormat($message->getHeaders()->getTimestamp())],
                ["Stacktrace", $this->getReadableStacktrace($message->getHeaders()->get(ErrorContext::EXCEPTION_STACKTRACE), $fullDetails)],
                ["Channel Name", $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME)],
                ["Content Type", $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID) ? $message->getHeaders()->get(MessageHeaders::TYPE_ID) : "Unknown"]
            ]
        );
    }

    /**
     * @OneTimeCommand("ecotone:dbal:deadletter:reply")
     */
    public function reply(DeadLetterGateway $deadLetterGateway, string $messageId) : void
    {
        $deadLetterGateway->reply($messageId);
    }

    /**
     * @OneTimeCommand("ecotone:dbal:deadletter:delete")
     */
    public function delete(DeadLetterGateway $deadLetterGateway, string $messageId) : void
    {
        $deadLetterGateway->delete($messageId);
    }

    /**
     * @OneTimeCommand("ecotone:dbal:deadletter:replyAll")
     */
    public function replyAll(DeadLetterGateway $deadLetterGateway) : void
    {
        $deadLetterGateway->replyAll();
    }

    private function convertTimestampToReadableFormat(int $timestamp)
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    private function getReadableStacktrace(string $strackTrace, bool $fullDetails): string
    {
        return $fullDetails ? $strackTrace : substr($strackTrace, 0, 200) . "...";
    }
}