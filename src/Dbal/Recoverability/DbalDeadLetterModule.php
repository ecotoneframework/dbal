<?php


namespace Ecotone\Dbal\Recoverability;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Enqueue\Dbal\DbalConnectionFactory;

/**
 * @ModuleAnnotation()
 */
class DbalDeadLetterModule implements AnnotationModule
{
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
        return "dbalRecoverabilityModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $connectionFactory     = DbalConnectionFactory::class;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                $connectionFactory     = $extensionObject->getDeadLetterConnectionReference();
            }
        }

        $configuration
            ->registerMessageHandler(DbalDeadLetterBuilder::createStore($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createDelete($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createGetDetails($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createList($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReply($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReplyAll($connectionFactory));
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