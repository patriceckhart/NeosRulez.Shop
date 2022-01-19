<?php
namespace NeosRulez\Shop\Service\Payment;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PayPalApi
 *
 * @Flow\Scope("singleton")
 */
class PayPalApi {

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Service
     */
    protected $i18nService;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\I18n\Translator
     */
    protected $translator;

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
     * @return string
     */
    public function execute(array $payment, array $args, string $success_uri):string
    {
        return '
            <style>body { overflow-x: hidden; }</style>
            <script src="https://www.paypal.com/sdk/js?client-id='.$this->settings['Payment']['paypalapi']['args']['client_id'].'&currency=EUR" ></script>
            <div style="width:100vw;height:100vh;display:flex;align-items:center;justify-content:center;">
                <div style="display:block;">
                    <div id="paypal-button-container"></div>
                    <div style="width:100%; display:block; margin-top:20px; font-family: Arial; font-size:1.2em; text-align:center;">
                    ' . $this->translator->translateById('doNotCloseWindow', [], null, null, $sourceName = 'Main', $packageKey = 'NeosRulez.Shop') . '
                    </div>
                </div>
            </div>
            <script>
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                custom_id: "'.$args['order_number'].'",
                                amount: {
                                    value: "' . (float) number_format(floatval($args['summary']['total']), 2, '.', '') . '"
                                }
                            }]
                        });
                    },
                    onApprove: function(data, actions) {
                        return actions.order.get().then(function (orderDetails) {
                            return actions.order.capture().then(function () {
                                location.href = "'.$success_uri.'";
                            });
                        });
                    }
                }).render("#paypal-button-container");
            </script>
        ';
    }

}
