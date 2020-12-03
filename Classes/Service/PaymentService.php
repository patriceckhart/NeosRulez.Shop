<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

/**
 * Class Payment
 *
 * @Flow\Scope("singleton")
 */
class PaymentService {

    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
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
    public function injectSettings(array $settings) {
        $this->settings = $settings;
    }

    public function __construct(\Neos\Flow\ObjectManagement\ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $args
     * @return string
     */
    public function initPayment($args) {
        $payment = $this->getPaymentByIdentifier($args['payment']);
        $payment_class = $this->objectManager->get($payment['class']);
        if($payment['props']['payment_status']=='1') {
            $success_uri = $args['success_uri'].'?order='.$args['order_number'].'&paid='.$payment['props']['payment_status'];
        } else {
            $success_uri = $args['success_uri'];
        }
        $failure_uri = $args['failure_uri'];
        return $payment_class->execute($payment, $args, $success_uri, $failure_uri);
    }

    /**
     * @param string $identifier
     * @return array
     */
    public function getPaymentByIdentifier($identifier) {
        $payments = $this->settings['Payment'];
        $selected_payment = $payments[$identifier];
        return $selected_payment;
    }

}
