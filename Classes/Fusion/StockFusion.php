<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class StockFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;


    /**
     * @return int
     */
    public function evaluate():int
    {
        $product = $this->fusionValue('product');
        $stockLevel = $this->fusionValue('stockLevel');
        $result = (int) $stockLevel;
        if($product && $stockLevel) {
            $result = $this->handleStockManagement($product->getIdentifier(), (int) $product->getProperty('stockLevel'));
        }
        return $result;
    }

    /**
     * @param string $nodeIdentifier
     * @param int $stockLevel
     * @return int
     */
    private function handleStockManagement(string $nodeIdentifier, int $stockLevel):int
    {
        $result = $stockLevel;
        $items = $this->cart->items;
        if(!empty($items)) {
            foreach ($items as $item) {
                if($item['node'] == $nodeIdentifier) {
                    $itemQuantity = (int) $item['quantity'];
                    $newStockLevel = $stockLevel - $itemQuantity;
                    if($newStockLevel > 0 || $newStockLevel == 0) {
                        $result = $newStockLevel;
                    }
                }
            }
        }
        return $result;
    }

}
