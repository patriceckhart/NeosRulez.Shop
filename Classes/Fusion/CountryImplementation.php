<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class CountryImplementation extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\DataSource\CountriesDataSource
     */
    protected $countriesDataSource;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @return array
     */
    public function evaluate() {
        $context = $this->contextFactory->create();
        $shippings = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Shipping]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        $all_countries = $this->countriesDataSource->getData();
        $result = array();
        if($shippings) {
            foreach ($shippings as $shipping) {
                $countries = $shipping->getProperty('countries');
                foreach ($countries as $country) {
                    $available_countries[] = $country;
                }
            }
            $available_countries = array_unique($available_countries);
            foreach ($available_countries as $available_country) {
                foreach ($all_countries as $all_country) {
                    if($all_country['value']==$available_country) {
                        $result[] = ['label' => $all_country['label'], 'value' => $all_country['value']];
                    }
                }
            }
        }
        return $result;
    }

}