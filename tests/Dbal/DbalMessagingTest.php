<?php


namespace Test\Ecotone\Dbal;


use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

abstract class DbalMessagingTest extends TestCase
{
    /**
     * @var DbalConnectionFactory|ManagerRegistryConnectionFactory
     */
    private $dbalConnectionFactory;

    public function getConnectionFactory(bool $isRegistry = false) : ConnectionFactory
    {
        if (!$this->dbalConnectionFactory) {
            $dbalConnectionFactory = new DbalConnectionFactory('pgsql://ecotone:secret@database:5432/ecotone');
            $this->dbalConnectionFactory = $isRegistry
                ? new ManagerRegistryConnectionFactory(
                    new DbalConnectionManagerRegistryWrapper($dbalConnectionFactory)
                )
                : $dbalConnectionFactory;
        }

        return $this->dbalConnectionFactory;
    }

    protected function getReferenceSearchServiceWithConnection()
    {
        return InMemoryReferenceSearchService::createWith([
            DbalConnectionFactory::class => $this->getConnectionFactory()
        ]);
    }
}