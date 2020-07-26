<?php

namespace Ecotone\Dbal\DbalTransaction;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
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

/**
 * @ModuleAnnotation()
 */
class DbalTransactionConfiguration implements AnnotationModule
{
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
        return "DbalTransactionModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $connectionFactories = [DbalConnectionFactory::class];
        $pointcut            = "@(" . DbalTransaction::class . ")";
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                if ($extensionObject->isDefaultTransactionOnAsynchronousEndpoints()) {
                    $pointcut .= "||@(" . AsynchronousRunningEndpoint::class . ")";
                }
                if ($extensionObject->isDefaultTransactionOnCommandBus()) {
                    $pointcut .= "||" . CommandBus::class . "";
                }
                if ($extensionObject->getDefaultConnectionReferenceNames()) {
                    $connectionFactories = $extensionObject->getDefaultConnectionReferenceNames();
                }
            }
        }

        $configuration
            ->requireReferences($connectionFactories)
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithObjectBuilder(
                    DbalTransactionInterceptor::class,
                    new DbalTransactionInterceptorBuilder($connectionFactories),
                    "transactional",
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE,
                    $pointcut
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