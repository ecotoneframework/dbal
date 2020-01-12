<?php


namespace Ecotone\Dbal;


use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\OutboundMessageConverter;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\DbalDestination;
use Enqueue\Dbal\DbalMessage;

class DbalOutboundChannelAdapter implements MessageHandler
{
    /**
     * @var CachedConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var bool
     */
    private $autoDeclare;
    /**
     * @var OutboundMessageConverter
     */
    private $outboundMessageConverter;
    /**
     * @var string
     */
    private $queueName;
    /**
     * @var bool
     */
    private $initialized = false;

    public function __construct(CachedConnectionFactory $connectionFactory, string $queueName, bool $autoDeclare, OutboundMessageConverter $outboundMessageConverter)
    {
        $this->connectionFactory = $connectionFactory;
        $this->autoDeclare = $autoDeclare;
        $this->outboundMessageConverter = $outboundMessageConverter;
        $this->queueName = $queueName;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        if ($this->autoDeclare && !$this->initialized) {
            /** @var DbalContext $context */
            $context = $this->connectionFactory->createContext();

            $context->createDataBaseTable();
            $context->createQueue($this->queueName);
            $this->initialized = true;
        }

        $outboundMessage = $this->outboundMessageConverter->prepare($message);
        $headers = $outboundMessage->getHeaders();
        $headers[MessageHeaders::CONTENT_TYPE] = $outboundMessage->getContentType();

        $messageToSend = new DbalMessage($outboundMessage->getPayload(), $headers, []);

        $this->connectionFactory->getProducer()
            ->setTimeToLive($outboundMessage->getTimeToLive())
            ->setDeliveryDelay($outboundMessage->getDeliveryDelay())
            ->send(new DbalDestination($this->queueName), $messageToSend);
    }
}