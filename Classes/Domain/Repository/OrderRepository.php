<?php
namespace NeosRulez\Shop\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class OrderRepository extends Repository {

    public function findByIdentifier($identifier) {
        $class = '\NeosRulez\Shop\Domain\Model\Order';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('Persistence_Object_Identifier', $identifier))->execute()->getFirst();
        return $result;
    }

    public function findByOrderNumber($order) {
        $class = '\NeosRulez\Shop\Domain\Model\Order';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->matching($query->equals('ordernumber', $order))->execute()->getFirst();
        return $result;
    }

}