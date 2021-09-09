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
     * @var \NeosRulez\Shop\Domain\Repository\InvoiceRepository
     */
    protected $invoiceRepository;

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
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\InvoiceService
     */
    protected $invoiceService;

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
            $order->firstname = json_decode($order->getInvoicedata())->firstname;
            $order->lastname = json_decode($order->getInvoicedata())->lastname;
            $order->email = json_decode($order->getInvoicedata())->email;
            $payment_label = $this->settings['Payment'][$order->getPayment()]['props']['label'];
            $invoice = $this->invoiceRepository->findByOrdernumber($order->getOrdernumber())->getFirst();
            $result[] = ['order' => $order, 'payment' => $payment_label, 'invoice' => $invoice];
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
        $cancelStatus = $order->getCanceled();
        if($cancelStatus) {
            $order->setCanceled(false);
        } else {
            $order->setCanceled(true);
        }
        $this->orderRepository->update($order);
        $this->persistenceManager->persistAll();
        $this->redirect('index');
    }

    /**
     * @param string $json
     * @return void
     */
    public function downloadInvoiceAction(string $json):void
    {
        $invoiceData['args'] = (array) json_decode($json);
        $cartVariables = (array) json_decode($json)->cart_variables;
        $invoiceData['args']['cart_variables'] = $cartVariables;
        $invoiceData['items'] = json_decode($this->orderRepository->findByOrderNumber((int) $invoiceData['args']['order_number'])->getCart());
        $invoiceData['args']['summary'] = json_decode($this->orderRepository->findByOrderNumber((int) $invoiceData['args']['order_number'])->getSummary());
        $this->invoiceService->createInvoice($invoiceData, false, true);
    }

}
