<?php
namespace NeosRulez\Shop\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * @Flow\Scope("singleton")
 */
class OrderController extends ActionController
{

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Repository\OrderRepository
     */
    protected $orderRepository;

    /**
     * @Flow\Inject
     * @var Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\FinisherService
     */
    protected $finisherService;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }


    /**
     * @return void
     */
    public function indexAction() {
        $orders = $this->orderRepository->findAll()->getQuery()->setOrderings(array('created' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING))->execute();
        $result = [];
        foreach ($orders as $order) {
            $payment_label = $this->settings['Payment'][$order->getPayment()]['props']['label'];
            $result[] = ['order' => $order, 'payment' => $payment_label];
        }
        $this->view->assign('orders', $result);
    }

    /**
     * @param \NeosRulez\Shop\Domain\Model\Order $order
     * @return void
     */
    public function showAction($order)
    {
        $context = $this->contextFactory->create();
        $payment_label = $this->settings['Payment'][$order->getPayment()]['props']['label'];
        $invoicedata = json_decode($order->getInvoicedata(), true);

        $shipping_node = $context->getNodeByIdentifier($invoicedata['shipping']);
        $shipping = $shipping_node->getProperty('title');
        $shipping_cost = $shipping_node->getProperty('price');

//        $this->view->assign('coupons', json_decode($order->getCoupons(), true));
//        $this->view->assign('summary', json_decode($order->getSummary(), true));

        $this->view->assign('shipping', $shipping);
        $this->view->assign('shippingcost', str_replace(',', '.', $shipping_cost));

        $cart = json_decode($order->getCart(), true);
        $this->view->assign('cart', $cart);

        $this->view->assign('invoicedata', $invoicedata);
        $this->view->assign('payment', $payment_label);
        $this->view->assign('order', $order);
    }

    /**
     * @param \NeosRulez\Shop\Domain\Model\Order $order
     * @return void
     */
    public function paidAction($order)
    {
        $this->finisherService->initAfterPaymentFinishers($order);
        $paid_status = $order->getPaid();
        if($paid_status == 1) {
            $order->setPaid(0);
        } else {
            $order->setPaid(1);
        }
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

    /**
     * @param \NeosRulez\Shop\Domain\Model\Order $order
     * @return void
     */
    public function cancelAction($order)
    {
        $cancel_status = $order->getCancelled();
        if($cancel_status == 1) {
            $order->setCancelled(0);
        } else {
            $order->setCancelled(1);
        }
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

}
