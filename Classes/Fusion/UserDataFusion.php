<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class UserDataFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \NeosRulez\Shop\Service\FrontendLoginService
     */
    protected $frontendLoginService;

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
     * @return array
     */
    public function evaluate() {
        $result = false;
        if($this->settings['useFeLogin']) {
            $loginRepository = $this->frontendLoginService->loginRepository();
            $username = $loginRepository->getUsername();
            $result = [];
            if($username) {
                $userRepository = $this->frontendLoginService->userRepository();
                $result = $userRepository->findByUsername($username);
            }
        }
        return $result;
    }

}
