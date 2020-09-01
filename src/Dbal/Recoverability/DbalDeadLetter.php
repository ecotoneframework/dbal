<?php declare(strict_types=1);


namespace Ecotone\Dbal\Recoverability;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Exception\Exception;
use function json_encode;

class DbalDeadLetter
{
    private ConnectionFactory $connectionFactory;
    private bool $isInitialized = false;
    private HeaderMapper $headerMapper;

    public function __construct(ConnectionFactory $connectionFactory, HeaderMapper $headerMapper)
    {
        $this->connectionFactory = $connectionFactory;
        $this->headerMapper = $headerMapper;
    }

    /**
     * @return ErrorContext[]
     */
    public function list(int $limit, int $offset): array
    {
        $this->initialize();
        $messages = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->execute()
            ->fetchAll();

        return array_map(function(array $message) {
            return ErrorContext::fromHeaders($this->decodeHeaders($message));
        }, $messages);
    }

    public function getDetails(string $messageId): Message
    {
        $this->initialize();
        $message = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->andWhere('message_id = :messageId')
            ->setParameter('messageId', $messageId, Types::TEXT)
            ->orderBy("failed_at", "ASC")
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (!$message) {
            throw InvalidArgumentException::create("Can not find message with id {$messageId}");
        }

        return MessageBuilder::withPayload($message['payload'])
                    ->setMultipleHeaders($this->decodeHeaders($message))
                    ->build();
    }

    public function reply(string $messageId, MessagingEntrypoint $messagingEntrypoint): void
    {
        $this->initialize();
        $message = $this->getDetails($messageId);
        $message = MessageBuilder::fromMessageWithPreservedMessageId($message)
                            ->removeHeaders([
                                ErrorContext::EXCEPTION_STACKTRACE,
                                ErrorContext::EXCEPTION_CODE,
                                ErrorContext::EXCEPTION_MESSAGE,
                                ErrorContext::EXCEPTION_FILE,
                                ErrorContext::EXCEPTION_LINE
                            ])
                            ->setHeader(MessagingEntrypoint::ENTRYPOINT, $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME))
                            ->build();

        $messagingEntrypoint->sendMessage($message);
        $this->delete($messageId);
    }

    public function replyAll(MessagingEntrypoint $messagingEntrypoint) : void
    {
        $this->initialize();
        while ($errorContexts = $this->list(100, 0)) {
            foreach ($errorContexts as $errorContext) {
                $this->reply($errorContext->getMessageId(), $messagingEntrypoint);
            }
        }
    }

    public function delete(string $messageId): void
    {
        $this->initialize();
        $this->getConnection()->createQueryBuilder()
            ->delete($this->getTableName())
            ->andWhere('message_id = :messageId')
            ->setParameter(':messageId', $messageId, Types::TEXT)
            ->execute();
    }

    public function store(Message $message): void
    {
        $this->initialize();
        $this->insertHandledMessage($message->getPayload(), $message->getHeaders()->headers());
    }

    private function insertHandledMessage(string $payload, array $headers): void
    {
        $rowsAffected = $this->getConnection()->insert(
            $this->getTableName(),
            [
                'message_id' => $headers[MessageHeaders::MESSAGE_ID],
                'failed_at' => $headers[MessageHeaders::TIMESTAMP],
                'payload' => $payload,
                'headers' => \json_encode($this->headerMapper->mapFromMessageHeaders($headers), JSON_THROW_ON_ERROR)
            ],
            [
                'message_id' => Types::TEXT,
                'failed_at' => Types::BIGINT,
                'payload' => Types::TEXT,
                'headers' => Types::TEXT
            ]
        );

        if (1 !== $rowsAffected) {
            throw new Exception('There was a problem inserting exceptional message. Dbal did not confirm that the record is inserted.');
        }
    }

    private function getTableName(): string
    {
        return "ecotone_error_messages";
    }

    private function createDataBaseTable(): void
    {
        $sm = $this->getConnection()->getSchemaManager();

        if ($sm->tablesExist([$this->getTableName()])) {
            return;
        }

        $table = new Table($this->getTableName());

        $table->addColumn('message_id', Types::TEXT);
        $table->addColumn('failed_at', Types::BIGINT);
        $table->addColumn('payload', Types::TEXT);
        $table->addColumn('headers', Types::TEXT);

        $table->setPrimaryKey(['message_id']);
        $table->addIndex(['failed_at']);

        $sm->createTable($table);
    }

    private function getConnection(): Connection
    {
        /** @var DbalContext $context */
        $context = $this->connectionFactory->createContext();

        return $context->getDbalConnection();
    }

    private function decodeHeaders($message) : array
    {
        return \json_decode($message['headers'], true, 512, JSON_THROW_ON_ERROR);
    }

    private function initialize(): void
    {
        if (!$this->isInitialized) {
            $this->createDataBaseTable();
            $this->isInitialized = true;
        }
    }
}