<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class SelectedCountryFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;


    /**
     * @return string
     */
    public function evaluate():string
    {
        $result = false;
        if($this->cart->getCountry() !== null) {
            $result = $this->cart->getCountry();
        }
        return $result;
    }

}
