<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class ShippingImplementation extends AbstractFusionObject {

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
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @return array
     */
    public function evaluate() {
        $selected_country = $this->cart->getCountry();
        $context = $this->contextFactory->create();
        $shippings = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Shipping]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        $available_shippings = array();
        if($shippings) {
            foreach ($shippings as $shipping) {
                $countries = $shipping->getProperty('countries');
                foreach ($countries as $country) {
                    if($country == $selected_country) {
                        $available_shippings[] = ['shipping' => $shipping, 'float_price' => floatval(str_replace(',', '.', $shipping->getProperty('price'))), 'price_kg' => $shipping->getProperty('price_kg')];
                    }
                }
            }
        }
        $result = array();
        $result[] = ['selected_country' => $selected_country, 'available_shippings' => $available_shippings];
        return $result;
    }

}