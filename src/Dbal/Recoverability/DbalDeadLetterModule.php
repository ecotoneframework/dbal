<?php


namespace Ecotone\Dbal\Recoverability;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Enqueue\Dbal\DbalConnectionFactory;

#[ModuleAnnotation]
class DbalDeadLetterModule implements AnnotationModule
{
    const LIST_COMMAND_NAME            = "ecotone:dbal:deadletter:list";
    const SHOW_COMMAND_NAME            = "ecotone:dbal:deadletter:show";
    const REPLY_COMMAND_NAME           = "ecotone:dbal:deadletter:reply";
    const REPLY_ALL_COMMAND_NAME       = "ecotone:dbal:deadletter:replyAll";
    const DELETE_COMMAND_NAME          = "ecotone:dbal:deadletter:delete";
    const HELP_COMMAND_NAME = "ecotone:dbal:deadletter:help";

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

        $this->registerOneTimeCommand("list", self::LIST_COMMAND_NAME, $configuration);
        $this->registerOneTimeCommand("show", self::SHOW_COMMAND_NAME, $configuration);
        $this->registerOneTimeCommand("reply", self::REPLY_COMMAND_NAME, $configuration);
        $this->registerOneTimeCommand("replyAll", self::REPLY_ALL_COMMAND_NAME, $configuration);
        $this->registerOneTimeCommand("delete", self::DELETE_COMMAND_NAME, $configuration);
        $this->registerOneTimeCommand("help", self::HELP_COMMAND_NAME, $configuration);

        $configuration
            ->registerMessageHandler(DbalDeadLetterBuilder::createStore($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createDelete($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createShow($connectionFactory))
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
        list($messageHandlerBuilder, $oneTimeCommandConfiguration) = ConsoleCommandModule::prepareConsoleCommand(
            DbalDeadLetterConsoleCommand::class, $methodName, $commandName
        );
        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($oneTimeCommandConfiguration);
    }
}