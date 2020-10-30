<?php
namespace NeosRulez\Shop\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * @Flow\Scope("singleton")
 */
class CartController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\PaymentService
     */
    protected $paymentService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\MailService
     */
    protected $mailService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\InvoiceService
     */
    protected $invoiceService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @param array $item
     * @return void
     */
    public function addAction($item) {
        $this->cart->add($item);
        $this->redirectToUri($item['nodeUri']);
    }

    /**
     * @param array $item
     * @return void
     */
    public function addCustomAction($item) {
        $this->cart->addCustom($item);
        $this->redirectToUri($item['nodeUri']);
    }

    /**
     * @param array $item
     * @param integer $quantity
     * @param string $return
     * @return void
     */
    public function updateAction($item, $quantity, $return) {
        $this->cart->updateItem($item, $quantity);
        $this->redirectToUri($return);
    }

    /**
     * @return void
     */
    public function cartAction() {
        return $this->cart->cart();
    }

    /**
     * @param string $code
     * @param string $return
     * @return void
     */
    public function validateCouponAction($code, $return) {
        $this->cart->deleteCoupons();
        $context = $this->contextFactory->create();
        $coupons = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Coupon]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
        if($coupons) {
            foreach ($coupons as $coupon) {
                $props = $coupon->getProperties();
                if($props['code'] == $code) {
                    if($props['quantity_restriction']) {
                        if(intval($props['redeemed']) < intval($props['quantity'])) {
                            $carttotal = $this->cart->getCartTotal();
                            if($carttotal >= floatval(str_replace(',', '.', $props['min_cart_value']))) {
                                $this->cart->applyCoupon($props['title'], $props['value'], $props['percentual']);
                            } else {
                                $this->cart->applyCoupon('NaN_', floatval(str_replace(',', '.', $props['min_cart_value'])), false);
                            }
                        }
                    } else {
                        $carttotal = $this->cart->getCartTotal();
                        if($carttotal >= floatval(str_replace(',', '.', $props['min_cart_value']))) {
                            $this->cart->applyCoupon($props['title'], $props['value'], $props['percentual']);
                        } else {
                            $this->cart->applyCoupon('NaN_', floatval(str_replace(',', '.', $props['min_cart_value'])), false);
                        }
                    }
                }
            }
        } else {
            $this->cart->applyCoupon('NaN', '0', false);
        }
        $this->redirectToUri($return);
    }

    /**
     * @param array $item
     * @param string $return
     * @return void
     */
    public function deleteItemAction($item, $return) {
        $this->cart->deleteItem($item);
        $this->redirectToUri($return);
    }

    /**
     * @param string $return
     * @return void
     */
    public function deleteCouponAction($return) {
        $this->cart->deleteCoupons();
        $this->redirectToUri($return);
    }

    /**
     * @param string $return
     * @return void
     */
    public function deleteCartAction($return) {
        $this->cart->deleteCart();
        $this->redirectToUri($return);
    }

    /**
     * @param string $return
     * @return void
     */
    public function checkoutAction($return) {
        $this->cart->setCheckout(true);
        $this->redirectToUri($return);
    }

    /**
     * @return void
     */
    public function updateOrderAction() {
        $args = $this->request->getArguments();
        $this->cart->setOrder($args);
        if (array_key_exists('selected_country', $args)) {
            if ($this->cart->getCountry() != $args['selected_country']) {
                $this->cart->unsetShipping();
            }
            $this->cart->setCountry($args['selected_country']);
        }
        $place_order = $this->cart->checkRequiredArgs($args);
        if($place_order) {
            if($this->settings['Mail']['debugMode']) {
                return $this->placeOrderAction($args);
            } else {
                $this->placeOrderAction($args);
            }
        } else {
            $this->redirectToUri($args['return']);
        }
    }

    /**
     * @return void
     */
    public function showCartAction() {
        $args = $this->request->getArguments();
        if (array_key_exists('cart', $args)) {
            if($args['cart']) {
                $this->cart->setCheckout(false);
            }
        }
        $this->redirectToUri($args['return']);
    }

    /**
     * @return void
     */
    public function finishAction() {
        $this->view->assign('main_content',$this->request->getInternalArgument('__main_content'));
        if (array_key_exists('paid', $_GET)) {
            $paid = isset($_GET['paid']) ? $_GET['paid']:NULL;
        }
        if (array_key_exists('order', $_GET)) {
            $order_number = isset($_GET['order']) ? $_GET['order']:NULL;
            $order = $this->orderRepository->findByOrderNumber($order_number);
            if($paid==1) {
                $order->setPaid(1);
                $this->orderRepository->update($order);
                $this->persistenceManager->persistAll();
            }
        }
        $this->cart->deleteCart();
    }

    /**
     * @param array $args
     * @return void
     */
    public function placeOrderAction($args) {
        $orders = $this->orderRepository->findAll();
        $oder_number = count($orders)+1;
        $args['order_number'] = $oder_number;
        $order = new \NeosRulez\Shop\Domain\Model\Order();
        $order->setOrdernumber($oder_number);
        $order->setInvoicedata(json_encode($args, JSON_UNESCAPED_UNICODE));
        $order->setCart(json_encode($this->cart->cart(), JSON_UNESCAPED_UNICODE));
        $order->setSummary(json_encode($this->cart->summary(), JSON_UNESCAPED_UNICODE));
        $order->setCoupons(json_encode($this->cart->coupons(), JSON_UNESCAPED_UNICODE));
        $order->setPayment($args['payment']);
        $order->setPaid(0);
        $user = $this->orderRepository->getUsername();
        if($user) {
            $order->setUser($user);
        }
        $this->orderRepository->add($order);
        $this->persistenceManager->persistAll();
        $args['summary'] = $this->cart->summary();
        if (strpos($args[$args['payment'].'_success'], 'http') !== false) {
            $success_page = $args[$args['payment'].'_success'];
        } else {
            $success_page = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$args[$args['payment'].'_success'];
        }
        if (strpos($args[$args['payment'].'_failure'], 'http') !== false) {
            $failure_page = $args[$args['payment'].'_failure'];
        } else {
            $failure_page = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".$args[$args['payment'].'_failure'];
        }
        $args['success_uri'] = $success_page;
        $args['failure_uri'] = $failure_page;
        $payment_data = $this->paymentService->getPaymentByIdentifier($args['payment']);
        $payment_url = $this->paymentService->initPayment($args);
        $args['payment_url'] = $payment_url;
        if($this->settings['Mail']['debugMode']) {
            return $this->mailService->execute($args);
        } else {
            if(!$this->settings['debugMode']) {
                $this->mailService->execute($args);
                $this->cart->refreshCoupons();
                $this->cart->deleteCart();
            }
            if (array_key_exists('render_redirect', $payment_data)) {
                if($payment_data['render_redirect']) {
                    $this->view->assign('output',$payment_url);
                }
            } else {
                $this->redirectToUri($payment_url);
            }
        }

    }

    /**
     * @return integer
     */
    public function countcartAction() {
        $result = 0;
        $items = $this->cart->cart();
        if($items) {
            foreach ($items as $item) {
                $quantity = intval($item['quantity']);
                $result = $result+$quantity;
            }
        }
        return $result;
    }

    /**
     * @return string
     */
    public function paymentSuccessAction() {
        return 'success';
    }

    /**
     * @return string
     */
    public function paymentFailureAction() {
        return 'failure';
    }

}
