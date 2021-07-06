<?php
namespace NeosRulez\Shop\Service\Payment;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class TestPayment
 *
 * @Flow\Scope("singleton")
 */
class TestPayment {

    /**
     * @param array $payment
     * @param array $args
     * @param string $success_uri
     * @param string $failure_uri
     * @return void
     */
    public function execute($payment, $args, $success_uri, $failure_uri) {
        return '
            <button id="pay" onclick="pay()">Simulate payment</button>
            <button id="cancel" onclick="cancel()">Simulate cancel payment</button>
            <p><code>' . json_encode($payment) . json_encode($args) . '</code></p>
            <script>
                    function pay() {
                        window.location.href = "' . $success_uri . '";
                    }
                    function cancel() {
                        window.location.href = "' . $failure_uri . '";
                    }
            </script>
        ';
    }

}
