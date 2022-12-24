<?php

namespace Ecotone\Dbal\Deduplication;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\Clock;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Exception\Exception;

/**
 * Class DbalTransactionInterceptor
 * @package Ecotone\Amqp\DbalTransaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DeduplicationInterceptor
{
    public const DEFAULT_DEDUPLICATION_TABLE = 'ecotone_deduplication';
    private bool $isInitialized = false;
    private Clock $clock;
    private int $minimumTimeToRemoveMessageInMilliseconds;
    private string $connectionReferenceName;

    public function __construct(string $connectionReferenceName, Clock $clock, int $minimumTimeToRemoveMessageInMilliseconds)
    {
        $this->clock = $clock;
        $this->minimumTimeToRemoveMessageInMilliseconds = $minimumTimeToRemoveMessageInMilliseconds;
        $this->connectionReferenceName = $connectionReferenceName;
    }

    public function deduplicate(MethodInvocation $methodInvocation, Message $message, ReferenceSearchService $referenceSearchService)
    {
        $connectionFactory = CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($referenceSearchService->get($this->connectionReferenceName)));

        if (! $this->isInitialized) {
            $this->createDataBaseTable($connectionFactory);
            $this->isInitialized = true;
        }
        $this->removeExpiredMessages($connectionFactory);
        $messageId = $message->getHeaders()->get(MessageHeaders::MESSAGE_ID);
        $consumerEndpointId = $message->getHeaders()->get(MessageHeaders::CONSUMER_ENDPOINT_ID);

        $select = $this->getConnection($connectionFactory)->createQueryBuilder()
            ->select('message_id')
            ->from($this->getTableName())
            ->andWhere('message_id = :messageId')
            ->andWhere('consumer_endpoint_id = :consumerEndpointId')
            ->andWhere('routing_slip = :routingSlip')
            ->setParameter('messageId', $messageId, Types::TEXT)
            ->setParameter('consumerEndpointId', $consumerEndpointId, Types::TEXT)
            ->setParameter('routingSlip', $message->getHeaders()->containsKey(MessageHeaders::ROUTING_SLIP) ? $message->getHeaders()->get(MessageHeaders::ROUTING_SLIP) : '', Types::TEXT)
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if ($select) {
            return;
        }

        $result = $methodInvocation->proceed();
        $this->insertHandledMessage($connectionFactory, $message->getHeaders()->headers());

        return $result;
    }

    private function removeExpiredMessages(ConnectionFactory $connectionFactory): void
    {
        $this->getConnection($connectionFactory)->createQueryBuilder()
            ->delete($this->getTableName())
            ->andWhere('(:now - handled_at) >= :minimumTimeToRemoveTheMessage')
            ->setParameter('now', $this->clock->unixTimeInMilliseconds(), Types::BIGINT)
            ->setParameter('minimumTimeToRemoveTheMessage', $this->minimumTimeToRemoveMessageInMilliseconds, Types::BIGINT)
            ->execute();
    }

    private function insertHandledMessage(ConnectionFactory $connectionFactory, array $headers): void
    {
        $rowsAffected = $this->getConnection($connectionFactory)->insert(
            $this->getTableName(),
            [
                'message_id' => $headers[MessageHeaders::MESSAGE_ID],
                'handled_at' => $this->clock->unixTimeInMilliseconds(),
                'consumer_endpoint_id' => $headers[MessageHeaders::CONSUMER_ENDPOINT_ID],
                'routing_slip' => $headers[MessageHeaders::ROUTING_SLIP] ?? '',
            ],
            [
                'id' => Types::TEXT,
                'handled_at' => Types::BIGINT,
                'consumer_endpoint_id' => Types::TEXT,
            ]
        );

        if (1 !== $rowsAffected) {
            throw new Exception('There was a problem inserting deduplication. Dbal did not confirm that the record is inserted.');
        }
    }

    private function getTableName(): string
    {
        return self::DEFAULT_DEDUPLICATION_TABLE;
    }

    private function createDataBaseTable(ConnectionFactory $connectionFactory): void
    {
        $sm = $this->getConnection($connectionFactory)->getSchemaManager();

        if ($sm->tablesExist([$this->getTableName()])) {
            return;
        }

        $table = new Table($this->getTableName());

        $table->addColumn('message_id', Types::STRING);
        $table->addColumn('consumer_endpoint_id', Types::STRING);
        $table->addColumn('routing_slip', Types::STRING);
        $table->addColumn('handled_at', Types::BIGINT);

        $table->setPrimaryKey(['message_id', 'consumer_endpoint_id', 'routing_slip']);
        $table->addIndex(['handled_at']);

        $sm->createTable($table);
    }

    private function getConnection(ConnectionFactory $connectionFactory): Connection
    {
        /** @var DbalContext $context */
        $context = $connectionFactory->createContext();

        return $context->getDbalConnection();
    }
}
