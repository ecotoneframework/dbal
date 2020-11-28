<?php

namespace Ecotone\Dbal\ObjectManager;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Annotation\ConsoleCommand;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\PollableEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Enqueue\Dbal\DbalConnectionFactory;

#[ModuleAnnotation]
class DbalTransactionModule implements AnnotationModule
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

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }
}