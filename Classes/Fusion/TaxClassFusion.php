<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class TaxClassFusion extends AbstractFusionObject {

//    /**
//     * @Flow\Inject
//     * @var \NeosRulez\CountryDataSource\Countries
//     */
//    protected $countries;
//
//    /**
//     * @Flow\Inject
//     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
//     */
//    protected $contextFactory;

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
        return $result;
    }

}
