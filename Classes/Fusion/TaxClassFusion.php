<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class TaxClassFusion extends AbstractFusionObject {


    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;


    /**
     * @return float
     */
    public function evaluate():float
    {
        $result = 0.00;
        $taxClassNode = $this->fusionValue('taxClass');
        $isCollection = $this->fusionValue('isCollection');

        if($isCollection) {
            $node = $this->fusionValue('node');
            $itemCollection = $node->getProperty('itemCollection');
            $taxes = [];
            if(!empty($itemCollection)) {
                foreach ($itemCollection as $item) {
                    $taxClass = $item->getProperty('taxClass');
                    $itemCountries = (new FlowQuery(array($taxClass)))->find('[instanceof NeosRulez.Shop:Content.Country]')->context(array('workspaceName' => 'live'))->filter('[_hiddenInIndex=false]')->get();
                    if(!empty($itemCountries)) {
                        foreach ($itemCountries as $itemCountry) {
                            $selectedCountry = $this->cart->getCountry();
                            $countries = $itemCountry->getProperty('countries');
                            if(!empty($countries)) {
                                foreach ($countries as $nodeCountry) {
                                    if($nodeCountry == $selectedCountry) {
                                        $taxes[] = $itemCountry->getProperty('taxValue');
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if(!empty($taxes)) {
                $maxTax = max($taxes);
                $result = (float) $maxTax;
            }
        } else {
            $allCountries = (new FlowQuery(array($taxClassNode)))->find('[instanceof NeosRulez.Shop:Content.Country]')->context(array('workspaceName' => 'live'))->filter('[_hiddenInIndex=false]')->get();
            if(!empty($allCountries)) {
                foreach ($allCountries as $country) {
                    if($country->hasProperty('countries')) {
                        $countries = $country->getProperty('countries');
                        if(!empty($countries)) {
                            foreach ($countries as $nodeCountry) {
                                $selectedCountry = $this->cart->getCountry();
                                if($nodeCountry == $selectedCountry) {
                                    $result = (float) $country->getProperty('taxValue');
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

}
