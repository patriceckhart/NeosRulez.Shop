<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class AvailableCountriesFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\CountryDataSource\Countries
     */
    protected $countries;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @return array
     */
    public function evaluate():array
    {
        $context = $this->contextFactory->create();
        $result = [];
        $allCountries = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Content.Country]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->filter('[_hiddenInIndex=false]')->get();
        $countries = [];
        if(!empty($allCountries)) {
            foreach ($allCountries as $allCountry) {
                if($allCountry->hasProperty('countries')) {
                    $country = $allCountry->getProperty('countries');
                    if(!empty($country)) {
                        foreach ($country as $countryItem) {
                            $countries[] = $countryItem;
                        }
                    }
                }
            }
            $uniqueCountries = array_unique($countries);
            $countriesString = implode(',', $uniqueCountries);
            $result = $this->countries->getCountries(false, $countriesString, false, false);
        }
        return $result;
    }

}
