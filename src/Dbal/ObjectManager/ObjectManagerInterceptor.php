<?php


namespace Ecotone\Dbal\ObjectManager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;

class ObjectManagerInterceptor
{
    /**
     * @var string[]
     */
    private $connectionReferenceNames;

    public function __construct(array $connectionReferenceNames)
    {
        $this->connectionReferenceNames = $connectionReferenceNames;
    }

    public function transactional(MethodInvocation $methodInvocation, ReferenceSearchService $referenceSearchService)
    {;
        /** @var ManagerRegistry[] $objectManagers */
        $objectManagers = [];

        foreach ($this->connectionReferenceNames as $connectionReferenceName) {
            $dbalConnectionFactory = $referenceSearchService->get($connectionReferenceName);
            if ($dbalConnectionFactory instanceof ManagerRegistryConnectionFactory) {
                $objectManagers[] =  DbalReconnectableConnectionFactory::getManagerRegistryAndConnectionName($dbalConnectionFactory)[0];
            }
        }

        foreach ($objectManagers as $objectManager) {
            foreach ($objectManager->getManagers() as $manager) {
                $manager->flush();
                $manager->clear();
            }
        }

        return $methodInvocation->proceed();
    }
}