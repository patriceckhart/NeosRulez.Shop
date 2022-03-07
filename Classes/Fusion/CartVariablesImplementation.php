<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class CartVariablesImplementation extends AbstractFusionObject {

    /**
     * @return array
     */
    public function evaluate() {
        $cart_variables[0] = ['recipient_mail' => $this->fusionValue('recipient_mail'), 'order_subject' => $this->fusionValue('order_subject'), 'mail_logo' => $this->fusionValue('mail_logo'), 'invoice' => $this->fusionValue('invoice'), 'invoice_background' => $this->fusionValue('invoice_background'), 'invoice_number' => $this->fusionValue('invoice_number'), 'invoice_number_prefix' => $this->fusionValue('invoice_number_prefix'), 'invoice_address' => $this->fusionValue('invoice_address'), 'invoice_info_prepayment' => $this->fusionValue('invoice_info_prepayment'), 'fiscal_year_start' => $this->fusionValue('fiscal_year_start'), 'fiscal_year_end' => $this->fusionValue('fiscal_year_end')];
        return $cart_variables;
    }

}