<?php
namespace NeosRulez\Shop\Domain\Model;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * @Flow\Scope("session")
 */
class Cart {

    /**
     * @var array
     */
    protected $items = array();

    /**
     * @var array
     */
    protected $coupons = array();

    /**
     * @var boolean
     */
    protected $checkout = false;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var array
     */
    protected $order = array();

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @param array $item
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function add($item) {
        $cart = $this->items;

        $quantity = intval($item['quantity']);

        $context = $this->contextFactory->create();
        $product_node = $context->getNodeByIdentifier($item['node']);

        $item['tstamp'] = time();
        $item['article_number'] = $product_node->getProperty('article_number');
        $item['title'] = $product_node->getProperty('title');
        $item['price_gross'] = floatval(str_replace(',', '.', $product_node->getProperty('price')));
        $item['tax'] = floatval(str_replace(',', '.', $product_node->getProperty('tax')));

        $min_quantity = $product_node->getProperty('min_quantity');
        $max_quantity = $product_node->getProperty('max_quantity');

        $item['weight'] = $product_node->getProperty('weight');

        if($min_quantity) {
            $item['min_quantity'] = $min_quantity;
        }

        if($max_quantity) {
            $item['max_quantity'] = $max_quantity;
        }

        $item['images'] = $product_node->getProperty('images');

        $item['tax_value_price'] = $item['price_gross']/100*$item['tax'];

        $item['price'] = $item['price_gross']-$item['tax_value_price'];

        $item['total'] = $item['price_gross']*$quantity;
        $item['tax_value_total'] = $item['total']/100*$item['tax'];

        $key = array_search($item['article_number'], array_column($cart, 'article_number'));

        $additional_price_gross = 0;
        $additional_tax_value_price = 0;

        $option_key = false;
        if (array_key_exists('options', $item)) {
            $option_key = array_search($item['options'], array_column($cart, 'options'));
            $combined_options = [];
            foreach ($item['options'] as $option) {
                $option_node = $context->getNodeByIdentifier($option);
                $option_price = floatval(str_replace(',', '.', str_replace('.', '', $option_node->getProperty('price'))));
                $combined_options[] = ['name' => $option_node->getProperty('title'), 'price' => $option_price * $quantity];
                $item['combined_options'] = $combined_options;
                $additional_price_gross = $additional_price_gross + $option_price;
                $additional_tax_value_price = $additional_price_gross/100*$item['tax'];
            }
        }

        $item['price'] = $item['price'] + $additional_price_gross - $additional_tax_value_price;
        $item['total'] = $item['total'] + $additional_price_gross;
        $item['price_gross'] = $item['price_gross'] + $additional_price_gross;

        if ($key === false) {
            $this->items[] = $item;
        } else {
            if ($option_key === false) {
                $this->items[] = $item;
            } else {
                $this->items[$key] = $item;
            }
        }

    }

    /**
     * @param array $item
     * @return void
     * @Flow\Session(autoStart = TRUE)
     */
    public function addCustom($item) {
        $cart = $this->items;

        $quantity = intval($item['quantity']);

        $item['tstamp'] = time();
        $item['price_gross'] = floatval(str_replace(',', '.', $item['price_gross']));
        $item['tax'] = floatval(str_replace(',', '.', $item['tax']));

        $item['tax_value_price'] = $item['price_gross']/100*$item['tax'];

        $item['price'] = $item['price_gross']-$item['tax_value_price'];

        $item['total'] = $item['price_gross']*$quantity;
        $item['tax_value_total'] = $item['total']/100*$item['tax'];

        $key = array_search($item['article_number'], array_column($cart, 'article_number'));

        $additional_price_gross = 0;
        $additional_tax_value_price = 0;

        $option_key = false;

        $item['price'] = $item['price'] + $additional_price_gross - $additional_tax_value_price;
        $item['total'] = $item['total'] + $additional_price_gross;
        $item['price_gross'] = $item['price_gross'] + $additional_price_gross;

        if ($key === false) {
            $this->items[] = $item;
        } else {
            if ($option_key === false) {
                $this->items[] = $item;
            } else {
                $this->items[$key] = $item;
            }
        }

    }

    /**
     * @param array $item
     * @param integer $quantity
     * @return void
     */
    public function updateItem($item, $quantity) {
        $cart = $this->items;
        $key = array_search($item['tstamp'], array_column($cart, 'tstamp'));
        $this->items[$key]['quantity'] = $quantity;
        $total = $this->items[$key]['price_gross']*$quantity;
        $this->items[$key]['total'] = $total;
    }

    /**
     * @return array
     */
    public function cart() {
        $cart = $this->items;
        return $cart;
    }

    /**
     * @return array
     */
    public function coupons() {
        $coupons = $this->coupons;
        return $coupons;
    }

    /**
     * @return boolean
     */
    public function checkout() {
        $checkout = $this->checkout;
        return $checkout;
    }

    /**
     * @return float
     */
    public function weight() {
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
    public function summary() {
        $summary = $this->items;
        $coupons = $this->coupons;
        $order = $this->order;
        $subtotal = 0;
        $total = 0;
        $total_coupon = 0;
        $tax_shipping = 0;
        $discount = 0;
        $total_shipping = 0;
        $shipping = [];
        $free_from = 9999999999999;
        $weight = 0;
        $itemweight = 0;
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
                    }
                }
            }
            $itemweight = $itemweight + intval($item['weight']);
            $itemweight = $itemweight * intval($item['quantity']);
        }
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
        }
        if($shipping) {
            if (array_key_exists('free_from', $shipping[0])) {
                if($shipping[0]['free_from'] == '') {
                    $free_from = 9999999999999;
                } else {
                    $free_from = floatval(str_replace(',', '.', $shipping[0]['free_from']));
                }
            }
            if($weight>0) {
                $shipping_weight = $shipping[0]['price'] * $weight;
                $tax_shipping = $shipping_weight / 100 * $shipping[0]['tax'];
                if($total_coupon<$free_from) {
                    $total_coupon = $total_coupon + $shipping_weight;
                }
                $total_shipping = $shipping_weight;
            } else {
                $tax_shipping = $shipping[0]['price'] / 100 * $shipping[0]['tax'];
                if($total_coupon<$free_from) {
                    $total_coupon = $total_coupon + $shipping[0]['price'];
                }
                $total_shipping = $shipping[0]['price'];
            }
            if($free_from && $total_coupon>=$free_from) {
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
        $result = ['subtotal' => $subtotal, 'tax' => $total-$subtotal, 'total_shipping' => $total_shipping, 'tax_shipping' => $tax_shipping, 'discount' => $discount, 'total' => $total_coupon, 'cartcount' => intval($cart_count), 'weight' => $itemweight, 'free_from' => $free_from];
        return $result;
    }

    /**
     * @return float
     */
    public function getCartTotal() {
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
    public function deleteItem($item) {
        $cart = $this->items;
        $key = array_search($item['tstamp'], array_column($cart, 'tstamp'));
        unset($this->items[$key]);
        sort($this->items);
    }

    /**
     * @return void
     */
    public function deleteCart() {
        $cart = $this->items;
        $cart_count = count($cart);
        for ($i = 0; $i < $cart_count; $i++) {
            unset($this->items[$i]);
        }
        $this->deleteCoupons();
        $this->deleteOrder();
        $this->checkout = false;
    }

    /**
     * @param string $name
     * @param float $value
     * @param boolean $percentual
     * @return void
     */
    public function applyCoupon($name, $value, $percentual) {
        $this->coupons[0] = ['name' => $name, 'value' => $value, 'percentual' => $percentual];
    }

    /**
     * @return void
     */
    public function deleteCoupons() {
        $coupons = $this->coupons;
        $coupons_count = count($coupons);
        for ($i = 0; $i < $coupons_count; $i++) {
            unset($this->coupons[$i]);
        }
    }

    /**
     * @return void
     */
    public function deleteOrder() {
        $order = $this->order;
        $order_count = count($order);
        for ($i = 0; $i < $order_count; $i++) {
            unset($this->order[$i]);
        }
    }

    /**
     * @param boolean $status
     * @return void
     */
    public function setCheckout($status) {
        if($status) {
            $this->checkout = true;
        } else {
            $this->checkout = false;
        }
    }

    /**
     * @param string $country
     * @return void
     */
    public function setCountry($country) {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountry() {
        $country = $this->country;
        return $country;
    }

    /**
     * @param array $args
     * @return void
     */
    public function setOrder($args) {
        unset($this->order[0]);
        $this->order[0] = $args;
    }

    /**
     * @return array
     */
    public function getOrder() {
        $order = $this->order;
        return $order;
    }

    /**
     * @return void
     */
    public function unsetShipping() {
        unset($this->order[0]['shipping']);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function findShipping($identifier) {
        $context = $this->contextFactory->create();
        $shipping_node = $context->getNodeByIdentifier($identifier);
        $result[] = ['name' => $shipping_node->getProperty('title'), 'price' => floatval(str_replace(',', '.', $shipping_node->getProperty('price'))), 'tax' => floatval(str_replace(',', '.', $shipping_node->getProperty('tax'))), 'price_kg' => $shipping_node->getProperty('price_kg'), 'free_from' => floatval(str_replace(',', '.', $shipping_node->getProperty('free_from')))];
        return $result;
    }

    /**
     * @param array $args
     * @return boolean
     */
    public function checkRequiredArgs($args) {
        $result = false;
        if (array_key_exists('firstname', $args) && array_key_exists('lastname', $args) && array_key_exists('country', $args) && array_key_exists('address', $args) && array_key_exists('zip', $args) && array_key_exists('city', $args) && array_key_exists('phone', $args) && array_key_exists('email', $args) && array_key_exists('shipping', $args) && array_key_exists('payment', $args) && array_key_exists('accept_terms', $args) && array_key_exists('accept_privacy', $args)) {
            if($args['firstname'] && $args['lastname'] && $args['country'] && $args['address'] && $args['zip'] && $args['city'] && $args['phone'] && $args['email'] && $args['shipping'] && $args['payment'] && $args['accept_terms'] && $args['accept_privacy']) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return void
     */
    public function refreshCoupons() {
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
