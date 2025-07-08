<?php
namespace NeosRulez\Shop\Service;

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use NeosRulez\Shop\Domain\Model\Cart;
use NeosRulez\Shop\Domain\Repository\OrderRepository;

/**
 * @Flow\Scope("singleton")
 */
class OrderService
{

    /**
     * @Flow\Inject
     * @var Cart
     */
    protected $cart;

    /**
     * @Flow\Inject
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param mixed $args
     * @return void
     */
    public function execute($args)
    {
        if(is_string($args)) {
            $args = json_decode($args, true);
        }
        if (array_key_exists('order_number', $args)) {
            $order = $this->orderRepository->findByOrdernumber($args['order_number']);
            if ($order !== null) {
                $context = $this->contextFactory->create();
                $siteNode = $context->getCurrentSiteNode();
                if ($siteNode !== null) {
                    $order->setSiteNode($siteNode->getName());
                    $coupons = $this->cart->coupons();
                    $order->setCoupons(json_encode($coupons));
                }
            }
            $this->orderRepository->update($order);
            $this->persistenceManager->persistAll();
        }
    }

}
