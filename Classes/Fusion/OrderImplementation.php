<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class OrderImplementation extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @return array
     */
    public function evaluate() {
        $order = $this->cart->getOrder();
        $result = array();
        if (array_key_exists(0, $order)) {
            $result = $order[0];
        }
        return $result;
    }

}