<?php
namespace NeosRulez\Shop\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\TypeHandling;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Symfony\Component\Yaml\Yaml;

class RelayDataSource extends AbstractDataSource {

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
     * @var string
     */
    protected static $identifier = 'neosrulez-shop-relays';

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments = array())
    {
        $options = [];
        $relays = $this->settings['quantityRelays'];
        if(!empty($relays)) {
            foreach ($relays as $i => $option) {
                $options[] = [
                    'label' => $option['label'] . ' (' . $option['relay'] . ')',
                    'value' => $i
                ];
            }
        }
        return $options;
    }

}
