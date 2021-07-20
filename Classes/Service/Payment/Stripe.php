<?php
namespace NeosRulez\Shop\Service\Payment;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Stripe
 *
 * @Flow\Scope("singleton")
 */
class Stripe {

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
     * @param array $payment
     * @param array $args
     * @param string $success_uri
     * @param string $failure_uri
     * @return void
     */
    public function execute($payment, $args, $success_uri, $failure_uri) {
        $secret_key = $this->settings['Payment']['creditcard']['args']['stripe_secret_key'];
        $public_key = $this->settings['Payment']['creditcard']['args']['stripe_public_key'];

        $amount = floatval($args['summary']['total'])*100;

        $stripe = new \Stripe\StripeClient(
            $secret_key
        );

        $product = $stripe->products->create([
            'name' => '#'.$args['order_number'],
        ]);
        $price = $stripe->prices->create([
            'unit_amount' => (int) $amount,
            'currency' => 'eur',
            'product' => $product,
        ]);
        $session = $stripe->checkout->sessions->create([
            'success_url' => $success_uri,
            'cancel_url' => $failure_uri,
            'payment_method_types' => [$payment['method']],
            'line_items' => [
                [
                    'price' => $price,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
        ]);

        \Neos\Flow\var_dump($price);

        return '
            <script src="https://js.stripe.com/v3/"></script>
            <script>
                var stripe = Stripe("'.$public_key.'");

                stripe.redirectToCheckout({
                sessionId: "'.$session->id.'"
                }).then(function (result) {
                    // If `redirectToCheckout` fails due to a browser or network
                    // error, display the localized error message to your customer
                    // using `result.error.message`.
                });
            </script>
        ';

    }

}
