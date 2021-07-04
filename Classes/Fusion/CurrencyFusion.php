<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class CurrencyFusion extends AbstractFusionObject {

    /**
     * @return string
     */
    public function evaluate() {
        $float = $this->fusionValue('float');
        $float = (float) str_replace(',', '.', $float);
        $decimalSeparator = $this->fusionValue('decimalSeparator') ? $this->fusionValue('decimalSeparator') : ',';
        $thousandsSeparator = $this->fusionValue('thousandsSeparator') ? $this->fusionValue('thousandsSeparator') : '.';
        $decimals = (int) $this->fusionValue('decimals') ? (int) $this->fusionValue('decimals') : 2;
        $currencySign = $this->fusionValue('currencySign');
        $result = false;
        if($float) {
            $result = number_format($float, $decimals, $decimalSeparator, $thousandsSeparator) . ($currencySign ? ' ' . $currencySign : false);
        }
        return $result;
    }

}
