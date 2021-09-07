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
        $freeShipping = $this->fusionValue('freeShipping');
        $result = [];
        $selected_country = $this->cart->getCountry();
        if($freeShipping) {
            $available_shippings[] = ['shipping' => ['identifier' => 1, 'properties' => ['title' => 'Versandkostenfrei']], 'float_price' => 0.00, 'price_kg' => 0.00, 'full_shipping_price' => 0.00, 'free_from' => 0.00];
            $result[] = ['selected_country' => $selected_country, 'available_shippings' => $available_shippings];
        } else {
            $context = $this->contextFactory->create();
            $shippings = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Shipping]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
            $available_shippings = array();
            $full_shipping_price = 0;
            if($shippings) {
                foreach ($shippings as $shipping) {
                    $countries = $shipping->getProperty('countries');
                    foreach ($countries as $country) {
                        if($country == $selected_country) {
                            $price_per_kg = $shipping->getProperty('price_kg');
                            if($price_per_kg) {
                                $float_price = floatval(str_replace(',', '.', $shipping->getProperty('price')));
                                $weight = $this->cart->weight();
                                $full_shipping_price = $float_price * $weight;
                            }
                            $available_shippings[] = ['shipping' => $shipping, 'float_price' => floatval(str_replace(',', '.', $shipping->getProperty('price'))), 'price_kg' => $shipping->getProperty('price_kg'), 'full_shipping_price' => $full_shipping_price, 'free_from' => floatval(str_replace(',', '.', $shipping->getProperty('free_from')))];
                        }
                    }
                }
            }
            $result = array();
            $result[] = ['selected_country' => $selected_country, 'available_shippings' => $available_shippings];
        }
        return $result;
    }

}
