<?php

namespace Ecotone\Dbal\DbalTransaction;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\PollableEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\LazyEventBus\LazyEventBusInterceptor;
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
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
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
        $pointcut = "@(" . DbalTransaction::class . ")";
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                if ($extensionObject->isDefaultTransactionOnPollableEndpoints()) {
                    $pointcut .= "||@(" . PollableEndpoint::class . ")";
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
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithObjectBuilder(
                    DbalTransactionInterceptor::class,
                    new DbalTransactionInterceptorBuilder($connectionFactories),
                    "transactional",
                    LazyEventBusInterceptor::PRECEDENCE * (-1),
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