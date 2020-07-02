<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class CartVariablesImplementation extends AbstractFusionObject {

    /**
     * @return array
     */
    public function evaluate() {
        $cart_variables[0] = ['recipient_mail' => $this->fusionValue('recipient_mail'), 'order_subject' => $this->fusionValue('order_subject'), 'mail_logo' => $this->fusionValue('mail_logo')];
        return $cart_variables;
    }

}