<?php

namespace Ecotone\Dbal\Deduplication;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\PollableEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Enqueue\Dbal\DbalConnectionFactory;

/**
 * @ModuleAnnotation()
 */
class DeduplicationConfiguration implements AnnotationModule
{
    const REMOVE_MESSAGE_AFTER_7_DAYS = 1000 * 60 * 60 * 24 * 7;

    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "DbalDeduplicationModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $isDeduplicatedEnabled = false;
        $connectionFactory     = [];
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                $connectionFactory     = $extensionObject->getDefaultConnectionReferenceNames();
                $isDeduplicatedEnabled = $extensionObject->isDeduplicatedEnabled();
            }
        }

        if (!$isDeduplicatedEnabled) {
            return;
        }

        if (empty($connectionFactory)) {
            $connectionFactory = [DbalConnectionFactory::class];
        }

        if (count($connectionFactory) !== 1) {
            throw ConfigurationException::create("For using duplication only one connection factory can be defined got: " . implode($connectionFactory));
        }

        $configuration
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithObjectBuilder(
                    DeduplicationInterceptor::class,
                    new DeduplicationInterceptorBuilder($connectionFactory[0], self::REMOVE_MESSAGE_AFTER_7_DAYS),
                    "deduplicate",
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE + 100,
                    "@(" . AsynchronousRunningEndpoint::class . ")"
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof DbalConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}