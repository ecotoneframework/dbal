<?php

namespace Ecotone\Dbal\Deduplication;

use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Scheduling\EpochBasedClock;

class DeduplicationInterceptorBuilder implements AroundInterceptorObjectBuilder
{
    /**
     * @var string
     */
    private $connectionReferenceName;
    /**
     * @var int
     */
    private $minimumTimeToRemoveMessageInMilliseconds;

    public function __construct(string $connectionReferenceName, int $minimumTimeToRemoveMessageInMilliseconds)
    {
        $this->connectionReferenceName = $connectionReferenceName;
        $this->minimumTimeToRemoveMessageInMilliseconds = $minimumTimeToRemoveMessageInMilliseconds;
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
            CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($referenceSearchService->get($this->connectionReferenceName))),
            new EpochBasedClock(),
            $this->minimumTimeToRemoveMessageInMilliseconds
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