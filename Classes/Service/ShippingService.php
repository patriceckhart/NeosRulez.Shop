<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Scope("singleton")
 */
class ShippingService {

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
     * @return array
     */
    public function execute():array
    {
        $result = [];
        if(array_key_exists('Shipping', $this->settings)) {
            if(array_key_exists('class', $this->settings['Shipping'])) {
                $shippingClass = $this->objectManager->get($this->settings['Shipping']['class']);
                $result = $shippingClass->execute();
            }
        }
        return $result;
    }

}
