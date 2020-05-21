<?php


namespace Ecotone\Dbal\DbalTransaction;

use Doctrine\DBAL\Connection;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\Dbal\DbalContext;

/**
 * Class DbalTransactionInterceptor
 * @package Ecotone\Amqp\DbalTransaction
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DbalTransactionInterceptor
{
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var string[]
     */
    private $connectionReferenceNames;

    public function __construct(ReferenceSearchService $referenceSearchService, array $connectionReferenceNames)
    {
        $this->referenceSearchService = $referenceSearchService;
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    public function transactional(MethodInvocation $methodInvocation, ?DbalTransaction $DbalTransaction)
    {;
        /** @var Connection[] $connections */
        $possibleConnections = array_map(function(string $connectionReferenceName){
            $connectionFactory = CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($this->referenceSearchService->get($connectionReferenceName)));

            /** @var DbalContext $context */
            $context = $connectionFactory->createContext();

            return  $context->getDbalConnection();
        }, $DbalTransaction ? $DbalTransaction->connectionReferenceNames : $this->connectionReferenceNames);

        $connections = [];
        foreach ($possibleConnections as $connection) {
            if ($connection->isTransactionActive()) {
                continue;
            }

            $connections[] = $connection;
        }

        foreach ($connections as $connection) {
            $connection->beginTransaction();
        }
        try {
            $result = $methodInvocation->proceed();

            foreach ($connections as $connection) {
                $connection->commit();
            }
        }catch (\Throwable $exception) {
            foreach ($connections as $connection) {
                $connection->rollBack();
            }

            throw $exception;
        }

        return $result;
    }
}