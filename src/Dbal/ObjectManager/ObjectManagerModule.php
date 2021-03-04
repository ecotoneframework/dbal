<?php

namespace Ecotone\Dbal\ObjectManager;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\PollableEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Enqueue\Dbal\DbalConnectionFactory;

#[ModuleAnnotation]
class ObjectManagerModule implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $connectionFactories = [DbalConnectionFactory::class];
        $pointcut = "(" . AsynchronousRunningEndpoint::class . ")";

        $dbalConfiguration = DbalConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                $dbalConfiguration = $extensionObject;
            }
        }

        if (!$dbalConfiguration->isClearObjectManagerOnAsynchronousEndpoints()) {
            return;
        }
        if ($dbalConfiguration->getDefaultConnectionReferenceNames()) {
            $connectionFactories = $dbalConfiguration->getDefaultConnectionReferenceNames();
        }

        $configuration
            ->requireReferences($connectionFactories)
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithDirectObject(
                    new ObjectManagerInterceptor($connectionFactories),
                    "transactional",
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE + 1,
                    $pointcut,
                    []
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

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}