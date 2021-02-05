<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class CurrencyFusion extends AbstractFusionObject {

    /**
     * @return string
     */
    public function evaluate() {
        $float = (float) $this->fusionValue('float');
        $decimalSeparator = $this->fusionValue('decimalSeparator');
        $thousandsSeparator = $this->fusionValue('thousandsSeparator');
        $decimals = (int) $this->fusionValue('decimals');
        $currencySign = $this->fusionValue('currencySign');
        $result = false;
        if($float) {
            $result = number_format($float, $decimals, $decimalSeparator, $thousandsSeparator) . ($currencySign ? ' ' . $currencySign : false);
        }
        return $result;
    }

}