<?php
namespace NeosRulez\Shop\Service\Payment;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PayPal
 *
 * @Flow\Scope("singleton")
 */
class Prepayment {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\StockService
     */
    protected $stockService;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\MailService
     */
    protected $mailService;


    /**
     * @param array $payment
     * @param array $args
     * @param string $success_uri
     * @param string $failure_uri
     * @return void
     */
    public function execute($payment, $args, $success_uri, $failure_uri) {
        $order = $this->orderRepository->findByOrderNumber($args['order_number']);
        $order->setCanceled(false);
        $this->orderRepository->update($order);

        $this->stockService->execute();
        $this->mailService->execute($args);
        $this->cart->refreshCoupons();

        return $success_uri;
    }

}
