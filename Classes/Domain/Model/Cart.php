<?php
namespace NeosRulez\Shop\Domain\Model;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\I18n\Translator;
use NeosRulez\Shop\Service\ShippingService;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * @Flow\Scope("session")
 */
class Cart
{

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var array
     */
    protected $coupons = [];

    /**
     * @var bool
     */
    protected $checkout = false;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var array
     */
    protected $order = [];

    /**
     * @var array
     */
    public $arguments = [];

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @Flow\Inject
     * @var ShippingService
     */
    protected $shippingService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @param array $item
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function add(array $item): void
    {
        $context = $this->contextFactory->create();

        if(array_key_exists('nodeLanguage', $item)) {
            $context = $this->contextFactory->create(
                array(
                    'dimensions' => array('language' => array($item['nodeLanguage'], $item['nodeLanguage']))
                ));
        }

        $quantity = intval($item['quantity']);

        $product_node = $context->getNodeByIdentifier($item['node']);

        $item['tstamp'] = time();
        $item['article_number'] = $product_node->getProperty('article_number');
        $item['title'] = $product_node->getProperty('title');
        $item['price_gross'] = floatval(str_replace(',', '.', $product_node->getProperty('price')));
        if(array_key_exists('customPrice', $item)) {
            $item['price_gross'] = floatval(str_replace(',', '.', $item['customPrice']));
        }
        $item['tax'] = floatval(str_replace(',', '.', $product_node->getProperty('tax')));
        if(array_key_exists('taxClass', $item)) {
            $item['tax'] = (float) $item['taxClass'];
            $priceGross = floatval(str_replace(',', '.', $product_node->getProperty('price')));
            $item['price_gross'] = $priceGross * ($item['tax'] / 100 + 1);
        }

        $item['relay'] = $product_node->getProperty('relay');

        $min_quantity = $product_node->getProperty('min_quantity');
        $max_quantity = $product_node->getProperty('max_quantity');

        $stockLevel = $product_node->getProperty('stockLevel');

        $item['weight'] = $product_node->getProperty('weight');

        if($product_node->hasProperty('freeShipping')) {
            $item['freeShipping'] = $product_node->getProperty('freeShipping');
        }

        if($min_quantity) {
            $item['min_quantity'] = $min_quantity;
        }

        if($max_quantity) {
            $item['max_quantity'] = $max_quantity;
        }

        if($stockLevel) {
            $item['max_quantity'] = $stockLevel;
        }

        $images = $product_node->getProperty('images');
        if(!empty($images)) {
            foreach ($images as $image) {
                $item['images'][] = $image->getIdentifier();
            }
        }

        $factor = floatval($item['tax'] / 100 + 1);
        $item['tax_value_price'] = $item['price_gross'] - ($item['price_gross'] / $factor);

        $item['price'] = $item['price_gross']-$item['tax_value_price'];

        $item['total'] = $item['price_gross']*$quantity;
        $item['tax_value_total'] = $item['total'] / $factor;

        $additional_price_gross = 0;
        $additional_tax_value_price = 0;

        $optionsT = '';
        $combined_options = [];
        if (array_key_exists('options', $item)) {
            foreach ($item['options'] as $option) {
                $option_node = $context->getNodeByIdentifier($option);
                $option_price = floatval(str_replace(',', '.', str_replace('.', '', $option_node->getProperty('price'))));
                $combined_options[] = ['name' => $option_node->getProperty('title'), 'price' => $option_price * $quantity];

                $optionsT .= $option_node->getProperty('title');
                $additional_price_gross = $additional_price_gross + $option_price;
                $additional_tax_value_price = $additional_price_gross / $factor;
            }
        }

        if (array_key_exists('options2', $item)) {
            foreach ($item['options2'] as $option2) {
                $combined_options[] = ['name' => $option2];
                $optionsT .= $option2;
            }
        }

        if (array_key_exists('options', $item) || array_key_exists('options2', $item)) {
            $item['combined_options'] = $combined_options;
        }

        $item['price'] = $item['price'] + $additional_price_gross - $additional_tax_value_price;
        $item['total'] = $item['total'] + $additional_price_gross;
        $item['price_gross'] = $item['price_gross'] + $additional_price_gross;

        $itemHash = md5($item['article_number'] . $optionsT);
        $item['hash'] = $itemHash;

        $this->addItem($item, $itemHash);

    }

    /**
     * @param array $item
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function addCustom(array $item): void
    {
        $cart = $this->items;

        $item['images'] = [];
        if(array_key_exists('node', $item)) {
            $context = $this->contextFactory->create();
            if(array_key_exists('nodeLanguage', $item)) {
                $context = $this->contextFactory->create(
                    array(
                        'dimensions' => array('language' => array($item['nodeLanguage'], $item['nodeLanguage']))
                    ));
            }
            $productNode = $context->getNodeByIdentifier($item['node']);
            if($productNode->hasProperty('images')) {
                $images = $productNode->getProperty('images');
                if(array_key_exists(0, $images)) {
                    $item['images'][] = $images[0]->getIdentifier();
                }
            }
            if($productNode->hasProperty('itemCollection')) {
                $items = $productNode->getProperty('itemCollection');
                if(array_key_exists(0, $items)) {
                    $itemNode = $items[0];
                    if($itemNode->hasProperty('images')) {
                        $images = $itemNode->getProperty('images');
                        if(array_key_exists(0, $images)) {
                            $item['images'][] = $images[0]->getIdentifier();
                        }
                    }
                }
            }
        }

        $quantity = intval($item['quantity']);

        $item['tstamp'] = time();
        $item['price_gross'] = floatval(str_replace(',', '.', $item['price_gross']));
        if(array_key_exists('tax', $item)) {
            $item['tax'] = floatval(str_replace(',', '.', $item['tax']));
        }
        if(array_key_exists('taxClass', $item)) {
            $item['tax'] = (float) $item['taxClass'];
            $priceGross = floatval(str_replace(',', '.', $item['price_gross']));
            $item['price_gross'] = $priceGross * ($item['tax'] / 100 + 1);
        }

        $factor = floatval($item['tax'] / 100 + 1);
        $item['tax_value_price'] = $item['price_gross'] - ($item['price_gross'] / $factor);

        $item['price'] = $item['price_gross']-$item['tax_value_price'];

        $item['total'] = $item['price_gross']*$quantity;
        $item['tax_value_total'] = $item['total'] / $factor;

        $key = array_search($item['article_number'], array_column($cart, 'article_number'));

        $additional_price_gross = 0;
        $additional_tax_value_price = 0;

        $option_key = false;
        $optionsT = '';
        if (array_key_exists('options', $item)) {
            $option_key = array_search($item['options'], array_column($cart, 'options'));
            $combined_options = [];
            foreach ($item['options'] as $option) {
                $combined_options[] = ['name' => $option['name'], 'price' => floatval(str_replace(',', '.', $option['price'])) * $quantity];
                $item['combined_options'] = $combined_options;
                $optionsT .= $option['name'];
                $additional_price_gross = $additional_price_gross + floatval(str_replace(',', '.', $option['price']));
                $additional_tax_value_price = $additional_price_gross / $factor;
            }
        }

        $item['price'] = $item['price'] + $additional_price_gross - $additional_tax_value_price;
        $item['total'] = $item['total'] + $additional_price_gross;
        $item['price_gross'] = $item['price_gross'] + $additional_price_gross;

        $itemHash = md5($item['article_number'] . $optionsT);
        $item['hash'] = $itemHash;

        $this->addItem($item, $itemHash);

    }

    /**
     * @param array $item
     * @param string $itemHash
     * @return void
     */
    public function addItem(array $item, string $itemHash): void
    {
        if(array_key_exists($item['hash'], $this->items)) {
            $quantity = $this->items[$itemHash]['quantity'];
            $newQuantity = $quantity + (int) $item['quantity'];
            $this->updateItem($this->items[$itemHash], $newQuantity);
        } else {
            $this->items[$itemHash] = $item;
        }
    }

    /**
     * @param array $item
     * @param int $quantity
     * @return void
     */
    public function updateItem(array $item, int $quantity): void
    {
        $this->items[$item['hash']]['quantity'] = $quantity;
        $total = $item['price_gross']*$quantity;
        $this->items[$item['hash']]['price'] = $item['price_gross'] - $item['tax_value_price'];
        $this->items[$item['hash']]['total'] = $total;
    }

    /**
     * @return array
     */
    public function cart(): array
    {
        $cart = $this->items;
        $items = [];
        if(!empty($cart)) {
            foreach ($cart as $item) {
                if(array_key_exists('images', $item)) {
                    $images = $item['images'];
                    if(!empty($images)) {
                        $item['images'] = [];
                        foreach ($images as $image) {
                            $item['images'][] = $this->assetRepository->findByIdentifier($image);
                        }
                    }
                }
                $item['foobar'] = 'foo';
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * @return array
     */
    public function coupons(): array
    {
        return $this->coupons;
    }

    /**
     * @return bool
     */
    public function checkout(): bool
    {
        return $this->checkout;
    }

    /**
     * @return float
     */
    public function weight(): float
    {
        $weight = 0;
        $summary = $this->items;
        foreach ($summary as $item) {
            $weight = $weight + intval($item['weight']) * intval($item['quantity']);
        }
        return $weight;
    }

    /**
     * @return array
     */
    public function summary(): array
    {
        $summary = $this->items;
        $coupons = $this->coupons;
        $order = $this->order;
        $subtotal = 0;
        $total = 0;
        $total_coupon = 0;
        $total_primary = 0;
        $tax_shipping = 0;
        $discount = 0;
        $total_shipping = 0;
        $shipping = [];
        $free_from = 9999999999999;
        $weight = 0;
        $pricekg = false;
        $itemweight = 0;
        $freeShipping = true;
        $graduatedShippingCosts = 0;
        $context = $this->contextFactory->create();
        $rateOnlyOnce = [];
        $rateCollected = [];
        if (array_key_exists(0, $order)) {
            if (array_key_exists('shipping', $order[0])) {
                $shipping = $this->findShipping($order[0]['shipping']);
            }
        }
        foreach ($summary as $item) {
            $subtotal = $subtotal+$item['price']*$item['quantity'];
            $total = $total+$item['total'];
            $total_coupon = $total_coupon+$item['total'];
            if (array_key_exists(0, $shipping)) {
                if (array_key_exists('price_kg', $shipping[0])) {
                    if($shipping[0]['price_kg']) {
                        $weight = $weight + intval($item['weight']) * intval($item['quantity']);
                        $pricekg = true;
                    }
                }
            }
            $itemweight = $itemweight + intval($item['weight']);
            $itemweight = $itemweight * intval($item['quantity']);

            if(array_key_exists('freeShipping', $item)) {
                if($item['freeShipping'] === false) {
                    $freeShipping = false;
                }
            } else {
                $freeShipping = false;
            }

            if(array_key_exists('images', $item)) {
                $images = $item['images'];
                if(!empty($images)) {
                    $item['images'] = [];
                    foreach ($images as $image) {
                        $item['images'][] = $this->assetRepository->findByIdentifier($image);
                    }
                }
            }

            if($shipping) {
                if (array_key_exists('node', $item)) {
                    $productNode = $context->getNodeByIdentifier($item['node']);
                    $quantity = (float)$item['quantity'];
                    if ($productNode !== null && $productNode->hasProperty('graduatedShippings')) {
                        $graduatedShippings = $productNode->getProperty('graduatedShippings');
                        if (count($graduatedShippings) > 0) {
                            foreach ($graduatedShippings as $graduatedShipping) {
                                foreach ($shipping as $shippingItem) {
                                    if($graduatedShipping->getParent()->getIdentifier() === $shippingItem['identifier']) {
                                        if($graduatedShipping->hasProperty('rateOnlyOnce') && $graduatedShipping->getProperty('rateOnlyOnce')) {
                                            if (in_array($graduatedShipping->getIdentifier(), $rateOnlyOnce) === false) {
                                                $rateOnlyOnce[] = $graduatedShipping->getIdentifier();
                                                $graduatedShippingCosts = $graduatedShippingCosts + $this->getGraduatedShipping($graduatedShipping, $quantity);
                                            }
                                        } else if($graduatedShipping->hasProperty('rateCollected') && $graduatedShipping->getProperty('rateCollected')) {
                                            $graduatedShippingCosts = 0;
                                            $rateCollected[$graduatedShipping->getIdentifier()]['graduatedShipping'] = $graduatedShipping;
                                            $rateCollected[$graduatedShipping->getIdentifier()]['quantity'][] = $quantity;
                                        } else {
                                            $graduatedShippingCosts = $graduatedShippingCosts + $this->getGraduatedShipping($graduatedShipping, $quantity);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($rateCollected as $rateCollectedItem) {
            $quantity = 0;
            foreach ($rateCollectedItem['quantity'] as $quantityItem) {
                $quantity = $quantity + (int) $quantityItem;
            }
            $graduatedShippingCosts = $graduatedShippingCosts + $this->getGraduatedShipping($rateCollectedItem['graduatedShipping'], $quantity);
        }


        $total_primary = $total_coupon;
        if($shipping) {
            if (array_key_exists('free_from', $shipping[0])) {
                if($shipping[0]['free_from'] === 0.00 || $shipping[0]['free_from'] === '' || $shipping[0]['free_from'] === null) {
                    $free_from = 9999999999999;
                } else {
                    $free_from = floatval(str_replace(',', '.', $shipping[0]['free_from']));
                }
            }
            if($pricekg) {
                $shipping_weight = $shipping[0]['price'] * $weight;

                $factor = floatval($shipping[0]['tax'] / 100 + 1);
                $tax_shipping = $shipping_weight - ($shipping_weight / $factor);

                if($total_coupon < $free_from) {
                    $total_coupon = $total_coupon + ($freeShipping ? 0 : $shipping_weight);
                }
                $total_shipping = $shipping_weight;
            } else {

                $factor = floatval($shipping[0]['tax'] / 100 + 1);
                $tax_shipping = $shipping[0]['price'] - ($shipping[0]['price'] / $factor);
                if($total_coupon < $free_from) {
                    $total_coupon = $total_coupon + ($freeShipping ? 0 : $shipping[0]['price']) + $graduatedShippingCosts;

                    if($shipping[0]['price'] === 0.00 && $graduatedShippingCosts !== 0.00) {
                        $tax_shipping = $graduatedShippingCosts - ($graduatedShippingCosts / $factor);
                    }
                } else {
                    $total_coupon = $total_coupon;
                }
                $total_shipping = $shipping[0]['price'];
            }
            if($free_from && $total_primary>=$free_from) {
                $total_shipping = 0;
                $tax_shipping = 0;
            }
            if($freeShipping) {
                $total_shipping = 0;
                $tax_shipping = 0;
            }
        }
        $cart_count = 0;
        if($summary) {
            foreach ($summary as $summaryitem) {
                $summaryquantity = intval($summaryitem['quantity']);
                $cart_count = $cart_count+$summaryquantity;
            }
        }

        if($free_from === 0.00) {
            $free_from = 9999999999999;
        }

        $total_shipping = $total_shipping + ($total_primary >= $free_from ? 0 : $graduatedShippingCosts);

        if($coupons) {
            if($coupons[0]['name'] != 'NaN' || $coupons[0]['name'] != 'NaN_') {
                if ($coupons[0]['percentual']) {
                    $discount = $total_coupon / 100 * floatval(str_replace(',', '.', $coupons[0]['value']));
                    $total_coupon = $total_coupon - $discount;
                } else {
                    if($coupons[0]['name'] != 'NaN_') {
                        $discount = floatval(str_replace(',', '.', $coupons[0]['value']));
                    } else {
                        $discount = 0;
                    }
                    $total_coupon = $total_coupon - $discount;
                }
            }
            if($coupons[0]['isShippingCoupon'] === true) {
                $total_coupon = $total_coupon - $total_shipping;
                $total_shipping = 0;
                $tax_shipping = 0;
            }
        }

        return ['subtotal' => $subtotal, 'tax' => ($total - $subtotal), 'total_tax' => $subtotal + ($total - $subtotal), 'total_shipping' => $total_shipping, 'tax_shipping' => $tax_shipping, 'discount' => $discount, 'total' => $total_coupon, 'cartcount' => intval($cart_count), 'weight' => $itemweight, 'free_from' => $free_from];
    }

    /**
     * @param string $price
     * @return float
     */
    private function convertStringToFloat(string $price): float
    {
        return (float) str_replace(',', '.', $price);
    }

    /**
     * @param Node $graduatedShippingNode
     * @param int $quantity
     * @return float
     */
    public function getGraduatedShipping(Node $graduatedShippingNode, int $quantity): float
    {
        $operator = $graduatedShippingNode->getProperty('operator');
        $price = $graduatedShippingNode->getProperty('price');
        $fromQuantity = (int) $graduatedShippingNode->getProperty('quantity');

        if($operator === '=') {
            if($quantity === $fromQuantity) {
                return $this->convertStringToFloat($price);
            }
        }
        if($operator === '>') {
            if($quantity > $fromQuantity) {
                return $this->convertStringToFloat($price);
            }
        }
        if($operator === '<') {
            if($quantity < $fromQuantity) {
                return $this->convertStringToFloat($price);
            }
        }
        if($operator === '>=') {
            if($quantity >= $fromQuantity) {
                return $this->convertStringToFloat($price);
            }
        }
        if($operator === '<=') {
            if($quantity <= $fromQuantity) {
                return $this->convertStringToFloat($price);
            }
        }

        return 0.00;
    }

    /**
     * @return float
     */
    public function getCartTotal(): float
    {
        $total = 0;
        $summary = $this->items;
        foreach ($summary as $item) {
            $total = $total+$item['total'];
        }
        return $total;
    }

    /**
     * @param array $item
     * @return void
     */
    public function deleteItem(array $item): void
    {
        unset($this->items[$item['hash']]);
    }

    /**
     * @return void
     */
    public function deleteCart(): void
    {
        foreach ($this->items as $item) {
            unset($this->items[$item['hash']]);
        }
        $this->deleteCoupons();
        $this->deleteOrder();
        $this->checkout = false;
    }

    /**
     * @param string $name
     * @param float $value
     * @param bool $percentual
     * @param bool $isShippingCoupon
     * @return void
     */
    public function applyCoupon(string $name, float $value, bool $percentual, bool $isShippingCoupon = false): void
    {
        $this->coupons[0] = ['name' => $name, 'value' => $value, 'percentual' => $percentual, 'isShippingCoupon' => $isShippingCoupon];
    }

    /**
     * @return void
     */
    public function deleteCoupons(): void
    {
        $coupons = $this->coupons;
        $coupons_count = count($coupons);
        for ($i = 0; $i < $coupons_count; $i++) {
            unset($this->coupons[$i]);
        }
    }

    /**
     * @return void
     */
    public function deleteOrder(): void
    {
        $order = $this->order;
        $order_count = count($order);
        for ($i = 0; $i < $order_count; $i++) {
            unset($this->order[$i]);
        }
    }

    /**
     * @param bool $status
     * @return void
     */
    public function setCheckout(bool $status): void
    {
        if($status) {
            $this->checkout = true;
        } else {
            $this->checkout = false;
        }
    }

    /**
     * @param string $country
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     * @Flow\Session(autoStart = TRUE)
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param array $args
     * @return void
     */
    public function setOrder(array $args): void
    {
        unset($this->order[0]);
        $this->order[0] = $args;
    }

    /**
     * @return array
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * @return void
     */
    public function unsetShipping(): void
    {
        unset($this->order[0]['shipping']);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function findShipping(string $identifier): array
    {
        $result = [];

        if($identifier == '1') {
            $result[] = ['identifier' => false, 'name' => $this->translator->translateById('content.freeShipping', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.Shop'), 'price' => 0.00, 'tax' => 0.00, 'price_kg' => 0.00, 'free_from' => 0.00];
        } else {
            $context = $this->contextFactory->create();
            $shipping_node = $context->getNodeByIdentifier($identifier);

            if(array_key_exists('Shipping', $this->settings)) {
                if (array_key_exists('class', $this->settings['Shipping'])) {
                    $result[] = $this->shippingService->execute();
                }
            } else {
                $result[] = ['identifier' => $shipping_node->getIdentifier(), 'name' => $shipping_node->getProperty('title'), 'price' => floatval(str_replace(',', '.', $shipping_node->getProperty('price'))), 'tax' => floatval(str_replace(',', '.', $shipping_node->getProperty('tax'))), 'price_kg' => $shipping_node->getProperty('price_kg'), 'free_from' => floatval(str_replace(',', '.', $shipping_node->getProperty('free_from')))];
            }

        }
        return $result;
    }

    /**
     * @param array $args
     * @return bool
     */
    public function checkRequiredArgs(array $args): bool
    {
        $result = false;
        if (array_key_exists('firstname', $args) && array_key_exists('lastname', $args) && array_key_exists('country', $args) && array_key_exists('address', $args) && array_key_exists('zip', $args) && array_key_exists('city', $args) /*&& array_key_exists('phone', $args)*/ && array_key_exists('email', $args) && array_key_exists('shipping', $args) && array_key_exists('payment', $args) && array_key_exists('accept_terms', $args) && array_key_exists('accept_privacy', $args)) {
            if($args['firstname'] && $args['lastname'] && $args['country'] && $args['address'] && $args['zip'] && $args['city'] /*&& $args['phone']*/ && $args['email'] && $args['shipping'] && $args['payment'] && $args['accept_terms'] && $args['accept_privacy']) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return void
     */
    public function refreshCoupons(): void
    {
        $coupons = $this->coupons();
        $context = $this->contextFactory->create();
        $all_coupons = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Coupon]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        foreach ($all_coupons as $coupon) {
            $title = $coupon->getProperty('title');
            if (array_key_exists(0, $coupons)) {
                if (array_key_exists('name', $coupons[0])) {
                    if($title==$coupons[0]['name']) {
                        $redeemed = $coupon->getProperty('redeemed');
                        $new_redeemed = intval($redeemed)+1;
                        $coupon->setProperty('redeemed', $new_redeemed);
                    }
                }
            }
        }
    }

}
