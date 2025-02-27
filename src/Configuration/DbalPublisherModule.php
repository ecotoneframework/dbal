<?php

namespace Ecotone\Dbal\Configuration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\DbalOutboundChannelAdapterBuilder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class DbalPublisherModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $registeredReferences = [];
        $applicationConfiguration = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults());

        foreach (ExtensionObjectResolver::resolve(DbalMessagePublisherConfiguration::class, $extensionObjects) as $dbalPublisher) {
            if (in_array($dbalPublisher->getReferenceName(), $registeredReferences)) {
                throw ConfigurationException::create("Registering two publishers under same reference name {$dbalPublisher->getReferenceName()}. You need to create publisher with specific reference using `createWithReferenceName`.");
            }

            $registeredReferences[] = $dbalPublisher->getReferenceName();
            $mediaType              = $dbalPublisher->getOutputDefaultConversionMediaType() ? $dbalPublisher->getOutputDefaultConversionMediaType() : $applicationConfiguration->getDefaultSerializationMediaType();

            $messagingConfiguration = $messagingConfiguration
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($dbalPublisher->getReferenceName(), MessagePublisher::class, 'send', $dbalPublisher->getReferenceName())
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($dbalPublisher->getReferenceName(), MessagePublisher::class, 'sendWithMetadata', $dbalPublisher->getReferenceName())
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($dbalPublisher->getReferenceName(), MessagePublisher::class, 'convertAndSend', $dbalPublisher->getReferenceName())
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create($dbalPublisher->getReferenceName(), MessagePublisher::class, 'convertAndSendWithMetadata', $dbalPublisher->getReferenceName())
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerMessageHandler(
                    DbalOutboundChannelAdapterBuilder::create($dbalPublisher->getQueueName(), $dbalPublisher->getConnectionReference())
                        ->withEndpointId($dbalPublisher->getReferenceName() . '.handler')
                        ->withInputChannelName($dbalPublisher->getReferenceName())
                        ->withAutoDeclareOnSend($dbalPublisher->isAutoDeclareQueueOnSend())
                        ->withHeaderMapper($dbalPublisher->getHeaderMapper())
                        ->withDefaultConversionMediaType($mediaType)
                );
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof DbalMessagePublisherConfiguration
            || $extensionObject instanceof ServiceConfiguration;
    }

    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::DBAL_PACKAGE;
    }
}
