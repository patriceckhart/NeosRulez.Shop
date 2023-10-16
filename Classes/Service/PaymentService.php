<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;

/**
 * Class Payment
 *
 * @Flow\Scope("singleton")
 */
class PaymentService {

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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

    public function __construct(ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $args
     * @return string
     */
    public function initPayment(array $args): string
    {
        $payment = $this->getPaymentByIdentifier($args['payment']);
        $paymentClass = false;
        if(array_key_exists('class', $payment)) {
            $paymentClass = $this->objectManager->get($payment['class']);
        } else {
            $payments = $this->settings['Payment']['payments'];
            foreach ($payments as $item) {
                if($item['nodeType'] === $payment['nodeType']) {
                    $paymentClass = $this->objectManager->get($item['class']);
                }
            }
        }
        $successUri = $args['success_uri'];
        $failureUri = $args['failure_uri'];
        if($paymentClass) {
            if(array_key_exists('props', $payment)) {
                if($payment['props']['payment_status'] == '1') {
                    $successUri = $args['success_uri'] . '?order=' . $args['order_number'] . '&paid=' . $payment['props']['payment_status'];
                }
            } else {
                $paymentStatus = array_key_exists('paymentStatus', $payment) && (bool) $payment['paymentStatus'];
                if($paymentStatus) {
                    $successUri = $args['success_uri'] . '?order=' . $args['order_number'] . '&paid=1';
                }
            }
        }
        return $paymentClass->execute($payment, $args, $successUri, $failureUri);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getPaymentByIdentifier(string $identifier): array
    {
        $payments = $this->settings['Payment'];
        if(array_key_exists($identifier, $payments)) {
            $selectedPayment = $payments[$identifier];
        } else {
            $context = $this->contextFactory->create();
            $paymentNode = $context->getNodeByIdentifier($identifier);
            if($paymentNode === null) {
                $selectedPayment['props']['label'] = $identifier;
                return $selectedPayment;
            }
            $selectedPayment = (array) $paymentNode->getProperties();
            $selectedPayment['nodeType'] = $paymentNode->getNodeType()->getName();
            $selectedPayment['props']['label'] = $paymentNode->getProperty('title');
            $selectedPayment['props']['payment_status'] = '0';
        }
        return $selectedPayment;
    }

}
