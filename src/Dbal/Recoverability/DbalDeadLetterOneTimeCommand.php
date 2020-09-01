<?php


namespace Ecotone\Dbal\Recoverability;


use Ecotone\Messaging\Annotation\OneTimeCommand;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\MessageHeaders;

class DbalDeadLetterOneTimeCommand
{
    const PAGE_LIMIT = 20;

    public function list(DeadLetterGateway $deadLetterGateway, int $page = 0) : OneTimeCommandResultSet
    {
        $limit = self::PAGE_LIMIT;
        $offset = $page * self::PAGE_LIMIT;

        return OneTimeCommandResultSet::create(
            ["Message Id", "Failed At", "Stacktrace"],
            array_map(function(ErrorContext $errorContext) {
                return [
                    $errorContext->getMessageId(),
                    $this->convertTimestampToReadableFormat($errorContext->getFailedTimestamp()),
                    $this->getReadableStacktrace($errorContext->getStackTrace(), false)
                ];
            }, $deadLetterGateway->list($limit, $offset))
        );
    }

    public function show(DeadLetterGateway $deadLetterGateway, string $messageId, bool $fullDetails = false) : OneTimeCommandResultSet
    {
        $message = $deadLetterGateway->show($messageId);

        return OneTimeCommandResultSet::create(
            [],
            [
                ["Message Id", $message->getHeaders()->getMessageId()],
                ["Failed At", $this->convertTimestampToReadableFormat($message->getHeaders()->getTimestamp())],
                ["Channel Name", $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME)],
                ["Content Type", $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID) ? $message->getHeaders()->get(MessageHeaders::TYPE_ID) : "Unknown"],
                ["Stacktrace", $this->getReadableStacktrace($message->getHeaders()->get(ErrorContext::EXCEPTION_STACKTRACE), $fullDetails)]
            ]
        );
    }

    public function reply(DeadLetterGateway $deadLetterGateway, string $messageId) : void
    {
        $deadLetterGateway->reply($messageId);
    }

    public function delete(DeadLetterGateway $deadLetterGateway, string $messageId) : void
    {
        $deadLetterGateway->delete($messageId);
    }

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
        return $fullDetails ? $strackTrace : substr($strackTrace, 0, 100) . "...";
    }
}