<?php


namespace Ecotone\Dbal\Recoverability;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\OneTimeCommandModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\OneTimeCommandConfiguration;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
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
        $isDeadLetterEnabled = false;
        $connectionFactory     = DbalConnectionFactory::class;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                if (!$extensionObject->isDeadLetterEnabled()) {
                    return;
                }

                $connectionFactory     = $extensionObject->getDeadLetterConnectionReference();
                $isDeadLetterEnabled = true;
            }
        }

        if (!$isDeadLetterEnabled) {
            return;
        }

        $this->registerOneTimeCommand("list", "ecotone:dbal:deadletter:list", $configuration);
        $this->registerOneTimeCommand("show", "ecotone:dbal:deadletter:show", $configuration);
        $this->registerOneTimeCommand("reply", "ecotone:dbal:deadletter:reply", $configuration);
        $this->registerOneTimeCommand("replyAll", "ecotone:dbal:deadletter:replyAll", $configuration);
        $this->registerOneTimeCommand("delete", "ecotone:dbal:deadletter:delete", $configuration);

        $configuration
            ->registerMessageHandler(DbalDeadLetterBuilder::createStore($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createDelete($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createGetDetails($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createList($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReply($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReplyAll($connectionFactory))
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                DeadLetterGateway::class,
                DeadLetterGateway::class,
                "list",
                DbalDeadLetterBuilder::LIST_CHANNEL
                )
                    ->withParameterConverters([
                        GatewayHeaderBuilder::create("limit", DbalDeadLetterBuilder::LIMIT_HEADER),
                        GatewayHeaderBuilder::create("offset", DbalDeadLetterBuilder::OFFSET_HEADER)
                    ])
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    "show",
                    DbalDeadLetterBuilder::SHOW_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    "reply",
                    DbalDeadLetterBuilder::REPLY_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    "replyAll",
                    DbalDeadLetterBuilder::REPLY_ALL_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    "delete",
                    DbalDeadLetterBuilder::DELETE_CHANNEL
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

    private function registerOneTimeCommand(string $methodName, string $commandName, Configuration $configuration): void
    {
        list($messageHandlerBuilder, $oneTimeCommandConfiguration) = OneTimeCommandModule::prepareOneTimeCommand(
            DbalDeadLetterOneTimeCommand::class, $methodName, $commandName
        );
        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerOneTimeCommand($oneTimeCommandConfiguration);
    }
}