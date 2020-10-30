<?php
namespace NeosRulez\Shop\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;

/**
 * @Flow\Scope("singleton")
 */
class OrderRepository extends Repository {

    /**
     * @var \Neos\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var \Neos\Flow\Security\Authentication\AuthenticationManagerInterface
     * @Flow\Inject
     */
    protected $authenticationManager;


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

    public function findSortedByOrdernumber() {
        $class = '\NeosRulez\Shop\Domain\Model\Order';
        $query = $this->persistenceManager->createQueryForType($class);
        $result = $query->setOrderings(array('ordernumber' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING))->execute();
        return $result;
    }

    /**
     * @return void
     */
    public function getUsername() {
        $result = false;
        if ($this->authenticationManager->isAuthenticated() === TRUE) {
            $account = $this->securityContext->getAccount();
            $result = $account->getAccountIdentifier();
        }
        return $result;
    }

}