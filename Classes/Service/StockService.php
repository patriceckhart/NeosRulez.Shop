<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * Class StockService
 *
 * @Flow\Scope("singleton")
 */
class StockService {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @return void
     */
    public function execute() {
        $items = $this->cart->items;
        $context = $this->contextFactory->create();
        foreach ($items as $item) {
            $nodeIdentifier = $item['node'];
            $node = $context->getNodeByIdentifier($nodeIdentifier);
            $stockLevel = (int) $node->getProperty('stockLevel');
            $orderedQuantity = (int) $item['quantity'];
            $newQuantity = ($stockLevel - $orderedQuantity);
            $node->setProperty('stockLevel', $newQuantity);
            if($newQuantity <= 0) {
                $node->setProperty('stock', false);
            }
        }
    }

}
