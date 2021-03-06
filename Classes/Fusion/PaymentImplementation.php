<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class PaymentImplementation extends AbstractFusionObject {

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

    /**
     * @return array
     */
    public function evaluate() {
        $context = $this->contextFactory->create();
        $payments = $this->settings['Payment'];
        foreach ($payments as $key => $payment) {
            $success_page = $context->getNodeByIdentifier($payment['props']['success_page']);
            $failure_page = $context->getNodeByIdentifier($payment['props']['failure_page']);
            $available_payments[] = ['identifier' => $key, 'label' => $payment['props']['label'], 'icon' => $payment['props']['icon'], 'success_page' => $success_page, 'failure_page' => $failure_page];
        }
        return $available_payments;
    }

}