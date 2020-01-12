<?php


namespace Test\Ecotone\Dbal;


use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Enqueue\Dbal\DbalConnectionFactory;
use PHPUnit\Framework\TestCase;

abstract class DbalMessagingTest extends TestCase
{
    /**
     * @var DbalConnectionFactory
     */
    private $dbalConnectionFactory;

    /**
     * @before
     */
    public function before() : void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->beginTransaction();
    }

    /**
     * @after
     */
    public function after(): void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->rollBack();
    }

    public function getConnectionFactory() : DbalConnectionFactory
    {
        if (!$this->dbalConnectionFactory) {
            $this->dbalConnectionFactory = new DbalConnectionFactory('pgsql://ecotone:secret@database:5432/ecotone');
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