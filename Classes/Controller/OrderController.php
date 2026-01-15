<?php
namespace NeosRulez\Shop\Controller;

use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use NeosRulez\Shop\Service\ExportService;

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
     * @var ContextFactory
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
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\MailService
     */
    protected $mailService;

    /**
     * @Flow\Inject
     * @var ExportService
     */
    protected $exportService;

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
            if(array_key_exists($order->getPayment(), $this->settings['Payment'])) {
                $payment_label = $this->settings['Payment'][$order->getPayment()]['props']['label'];
            } else {
                $payment_label = $order->getPayment();
            }
            $invoice = $this->invoiceRepository->findByOrdernumber($order->getOrdernumber())->getFirst();
//            $result[] = ['order' => $order, 'payment' => $payment_label, 'invoice' => $invoice];

            $order->order = $order;
            $order->paymentLabel = $payment_label;
            $order->invoice = $invoice;
        }
        $this->view->assign('orders', $orders);
    }

    /**
     * @param \NeosRulez\Shop\Domain\Model\Order $order
     * @return void
     */
    public function showAction($order)
    {
        $context = $this->contextFactory->create();

        $invoicedata = json_decode($order->getInvoicedata(), true);

        $shipping = '';
        $shipping_cost = '0,00';
        $shipping_node = $context->getNodeByIdentifier($invoicedata['shipping']);
        if(!empty($shipping_node)) {
            $shipping = $shipping_node->getProperty('title');
            $shipping_cost = $shipping_node->getProperty('price');
        }

        if(array_key_exists($order->getPayment(), $this->settings['Payment'])) {
            $payment_label = $this->settings['Payment'][$order->getPayment()]['props']['label'];
        } else {
            $payment_label = $order->getPayment();
        }

        $this->view->assign('shipping', $shipping);
        $this->view->assign('shippingcost', str_replace(',', '.', $shipping_cost));


        $cart = json_decode($order->getCart(), true);
        $this->view->assign('cart', $cart);

        $this->view->assign('invoice', $this->invoiceRepository->findByOrdernumber($order->getOrdernumber())->getFirst());
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
        $paid_status = $order->getPaid();
        if($paid_status == 1) {
            $order->setPaid(0);
        } else {
            $order->setPaid(1);
            $this->finisherService->initAfterPaymentFinishers($order->getInvoicedata());
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
     * @param bool $canceled
     * @return void
     */
    public function downloadInvoiceAction(string $json, bool $canceled = false):void
    {
        $invoiceData['args'] = (array) json_decode($json);
        $cartVariables = (array) json_decode($json)->cart_variables;
        $invoiceData['args']['cart_variables'] = $cartVariables;
        $invoiceData['items'] = json_decode($this->orderRepository->findByOrderNumber((int) $invoiceData['args']['order_number'])->getCart());
        $invoiceData['args']['summary'] = json_decode($this->orderRepository->findByOrderNumber((int) $invoiceData['args']['order_number'])->getSummary());
        $this->invoiceService->createInvoice($invoiceData, false, true, $canceled);
    }

    /**
     * @param \NeosRulez\Shop\Domain\Model\Order $order
     * @return void
     */
    public function reminderAction(\NeosRulez\Shop\Domain\Model\Order $order) {
        $variables['args'] = json_decode($order->getInvoicedata(), true);
        $variables['items'] = json_decode($order->getCart(), true);
        $variables['args']['summary'] = json_decode($order->getSummary(), true);
        $variables['header'] = 'Erinnerung: Es ist noch eine Zahlung offen';
        $variables['payment_service'] = [];
        $variables['url'] = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'];

        $this->mailService->send($variables, $variables['header'], [$this->settings['Mail']['senderMail'] => $this->settings['Mail']['senderMail']], [str_replace(' ', '', $variables['args']['email']) => $variables['args']['firstname'].' '.$variables['args']['lastname']], $variables['args']);
        $this->redirect('index');
    }

    /**
     * @return void
     */
    public function exportAction(): void
    {
        $this->redirectToUri($this->exportService->exportOrders());
    }

}
