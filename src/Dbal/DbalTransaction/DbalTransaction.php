<?php

namespace Ecotone\Dbal\DbalTransaction;

use Enqueue\Dbal\DbalConnectionFactory;

/**
 * @Annotation
 */
class DbalTransaction
{
    public $connectionReferenceNames = [DbalConnectionFactory::class];
}