<?php
namespace NeosRulez\Shop\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Payment
 *
 * @Flow\Scope("singleton")
 */
class FrontendLoginService {

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
     * @return void
     */
    public function loginRepository() {
        $result = $this->objectManager->get('NeosRulez\FrontendLogin\Domain\Repository\LoginRepository');
        return $result;
    }

    /**
     * @return void
     */
    public function userRepository() {
        $result = $this->objectManager->get('NeosRulez\FrontendLogin\Domain\Repository\UserRepository');
        return $result;
    }

}
