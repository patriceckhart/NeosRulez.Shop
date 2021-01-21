<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class RelayImplementation extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Domain\Model\Cart
     */
    protected $cart;

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
     * @return boolean
     */
    public function evaluate() {
        $items = $this->cart->cart();
        $relays = $this->settings['quantityRelays'];
        $results = [];
        $outputs = [];
        $valid = true;
        if(!empty($relays)) {
            foreach ($relays as $i => $relay) {
                $results[$i] = [$relay, 'quantity' => 0, 'valid' => 0];
            }
            foreach ($items as $item) {
                if(!is_null($item['relay'])) {
                    $results[$item['relay']]['quantity'] = $results[$item['relay']]['quantity'] + $item['quantity'];
                }
            }
            foreach ($results as $i => $result) {
                $division = $result['quantity'] / $result[0]['relay'];
                $outputs[$i] = 0;
                if(is_int($division)) {
                    $outputs[$i] = 1;
                }
            }
            foreach ($outputs as $output) {
                if($output == 0) {
                    $valid = false;
                    break;
                }
            }
        }
        return $valid;
    }

}