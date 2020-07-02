<?php
namespace NeosRulez\Shop\Service\Payment;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PayPal
 *
 * @Flow\Scope("singleton")
 */
class PayPal {

    /**
     * @param array $payment
     * @param array $args
     * @param string $success_uri
     * @param string $failure_uri
     * @return void
     */
    public function execute($payment, $args, $success_uri, $failure_uri) {
        return 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&hosted_button_id=123&tax=0&no_note=0&address_override=<1>&first_name='.$args['firstname'].'&last_name='.$args['lastname'].'&address='.$args['address'].'&city='.$args['city'].'&zip='.$args['city'].'&email='.$args['email'].'&country='.$args['country'].'&state=NaN&item_name=Order'.$args['order_number'].'&item_number='.$args['order_number'].'&return='.$success_uri.'&currency_code=EUR&amount='.$args['summary']['total'].'&business='.$payment['args']['paypal_account'];
    }

}
