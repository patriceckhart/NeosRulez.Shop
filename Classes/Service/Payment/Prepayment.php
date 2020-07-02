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
     * @param array $payment
     * @param array $args
     * @param string $success_uri
     * @param string $failure_uri
     * @return void
     */
    public function execute($payment, $args, $success_uri, $failure_uri) {
        return $success_uri;
    }

}
