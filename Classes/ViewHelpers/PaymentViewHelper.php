<?php
namespace NeosRulez\Shop\ViewHelpers;

use Neos\Flow\Annotations as Flow;
use NeosRulez\Shop\Service\PaymentService;

class PaymentViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @Flow\Inject
     * @var PaymentService
     */
    protected $paymentService;

    public function initializeArguments()
    {
        $this->registerArgument('string', 'string', 'identifier to find payment', true);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if(array_key_exists('title', $this->paymentService->getPaymentByIdentifier($this->arguments['string']))) {
            return $this->paymentService->getPaymentByIdentifier($this->arguments['string'])['title'];
        }
        return $this->arguments['string'];
    }

}
