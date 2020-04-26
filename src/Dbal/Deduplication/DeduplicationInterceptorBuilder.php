<?php

namespace Ecotone\Dbal\Deduplication;

use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

class DeduplicationInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @var string
     */
    private $connectionReferenceName;

    public function __construct(string $connectionReferenceName)
    {
        $this->connectionReferenceName = $connectionReferenceName;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return DeduplicationInterceptor::class;
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): object
    {
        return new DeduplicationInterceptor(
            CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($referenceSearchService->get($this->connectionReferenceName)))
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}