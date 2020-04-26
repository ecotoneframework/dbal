<?php

namespace Test\Ecotone\Dbal\Deduplication;

use Ecotone\Dbal\Deduplication\DeduplicationInterceptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Test\Ecotone\Dbal\DbalMessagingTest;
use Test\Ecotone\Dbal\Fixture\StubMethodInvocation;

class DbalTransactionInterceptorTest extends DbalMessagingTest
{
    /**
     * @before
     */
    public function before() : void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->beginTransaction();
    }

    /**
     * @after
     */
    public function after(): void
    {
        $this->getConnectionFactory()->createContext()->getDbalConnection()->rollBack();
    }

    public function test_not_handling_same_message_twice()
    {
        $dbalTransactionInterceptor = new DeduplicationInterceptor($this->getConnectionFactory(), new EpochBasedClock(), 1000);

        $methodInvocation = StubMethodInvocation::create();

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint1"
        ]);

        $this->assertEquals(1, $methodInvocation->getCalledTimes());

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint1"
        ]);

        $this->assertEquals(1, $methodInvocation->getCalledTimes());
    }

    public function test_not_deduplicating_for_different_endpoints()
    {
        $dbalTransactionInterceptor = new DeduplicationInterceptor($this->getConnectionFactory(), new EpochBasedClock(), 1000);

        $methodInvocation = StubMethodInvocation::create();

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint1"
        ]);

        $this->assertEquals(1, $methodInvocation->getCalledTimes());

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint2"
        ]);

        $this->assertEquals(2, $methodInvocation->getCalledTimes());
    }

    public function test_handling_message_with_same_id_when_it_was_removed_by_time_limit()
    {
        $dbalTransactionInterceptor = new DeduplicationInterceptor($this->getConnectionFactory(), new EpochBasedClock(), 1);

        $methodInvocation = StubMethodInvocation::create();

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint1"
        ]);

        $this->assertEquals(1, $methodInvocation->getCalledTimes());

        $dbalTransactionInterceptor->deduplicate($methodInvocation, [], [
            MessageHeaders::MESSAGE_ID => 1,
            MessageHeaders::CONSUMER_ENDPOINT_ID => "endpoint1"
        ]);

        $this->assertEquals(2, $methodInvocation->getCalledTimes());
    }

}