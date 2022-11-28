<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use NeosRulez\Shop\Domain\Model\Cart;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class StockService
{

    /**
     * @Flow\Inject
     * @var Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @return void
     */
    public function execute(): void
    {
        $items = $this->cart->items;
        $context = $this->contextFactory->create();
        foreach ($items as $item) {
            if(array_key_exists('node', $item)) {
                $nodeIdentifier = $item['node'];
                $node = $context->getNodeByIdentifier($nodeIdentifier);
                if($node !== null) {
                    if($node->hasProperty('stockLevel')) {
                        $stockLevel = $node->getProperty('stockLevel');
                        $stockManagement = (int) $node->getProperty('stockManagement');
                        if($stockManagement) {
                            $orderedQuantity = (int) $item['quantity'];
                            $newQuantity = ($stockLevel - $orderedQuantity);
                            $node->setProperty('stockLevel', $newQuantity);
                            if($newQuantity <= 0) {
                                $node->setProperty('stock', false);
                            }
                        }
                    }
                }
            }
        }
    }

}
