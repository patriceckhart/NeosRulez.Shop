<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Eel\FlowQuery\Operations;

/**
 * Class Payment
 *
 * @Flow\Scope("singleton")
 */
class FinisherService {

    /**
     * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
     */
    protected $objectManager;

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
     * @return void
     */
    public function initAfterOrderFinishers($args) {
        if(array_key_exists('Finisher', $this->settings)) {
            $finishers = $this->settings['Finisher']['afterOrder'];
            if($finishers) {
                foreach ($finishers as $finisher) {
                    $finisher_class = $this->objectManager->get($finisher['class']);
                    $finisher_class->execute($args);
                }
            }
        }
    }

    /**
     * @param array $args
     * @return void
     */
    public function initAfterPaymentFinishers($args) {
        if(array_key_exists('Finisher', $this->settings)) {
            $finishers = $this->settings['Finisher']['afterPayment'];
            if ($finishers) {
                foreach ($finishers as $finisher) {
                    $finisher_class = $this->objectManager->get($finisher['class']);
                    $finisher_class->execute($args);
                }
            }
        }
    }

}
