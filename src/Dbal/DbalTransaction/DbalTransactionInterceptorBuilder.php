<?php


namespace Ecotone\Dbal\DbalTransaction;


use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

class DbalTransactionInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @var array
     */
    private $connectionReferenceNames = [];

    public function __construct(array $connectionReferenceNames)
    {
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return DbalTransactionInterceptor::class;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): object
    {
        return new DbalTransactionInterceptor($referenceSearchService, $this->connectionReferenceNames);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}