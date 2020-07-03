<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class CartCountHelperImplementation extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @return integer
     */
    public function evaluate() {
        $result = 0;
        $items = $this->cart->cart();
        if($items) {
            foreach ($items as $item) {
                $quantity = intval($item['quantity']);
                $result = $result+$quantity;
            }
        }
        return $result;
    }

}