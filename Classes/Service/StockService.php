<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use NeosRulez\Shop\Domain\Model\Cart;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class StockService
{

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @return void
     */
    public function execute(): void
    {
        $items = $this->cart->items;
        $context = $this->contextFactory->create();
        $connection = $this->entityManager->getConnection();
        foreach ($items as $item) {
            if (array_key_exists('node', $item)) {
                $nodeIdentifier = $item['node'];
                $node = $context->getNodeByIdentifier($nodeIdentifier);
                $rows = $connection->executeQuery('select * from neos_contentrepository_domain_model_nodedata where identifier="' . $node->getIdentifier() . '"')->fetchAllAssociative();
                foreach ($rows as $row) {
                    $dimensionNode = $this->persistenceManager->getObjectByIdentifier($row['persistence_object_identifier'], 'Neos\ContentRepository\Domain\Model\NodeData');
                    if($dimensionNode !== null) {
                        if($dimensionNode->hasProperty('stockLevel')) {
                            $stockLevel = $dimensionNode->getProperty('stockLevel');
                            $stockManagement = (int) $dimensionNode->getProperty('stockManagement');
                            if($stockManagement) {
                                $orderedQuantity = (int) $item['quantity'];
                                $newQuantity = ($stockLevel - $orderedQuantity);
                                $dimensionNode->setProperty('stockLevel', $newQuantity);
                                if($newQuantity <= 0) {
                                    $dimensionNode->setProperty('stock', false);
                                }
                                $this->persistenceManager->persistAll();
                            }
                        }
                    }
                }

            }
        }
    }

}
