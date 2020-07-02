<?php
namespace NeosRulez\Shop\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\TypeHandling;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Symfony\Component\Yaml\Yaml;

class CountriesDataSource extends AbstractDataSource {

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
    protected static $identifier = 'neosrulez-shop-countries';

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments = array())
    {
        $options = [];
        $metadata = $this->loadMetaData();
        foreach ($metadata as $i => $option) {
            $options[] = [
                'label' => $option['label'],
                'value' => $i
            ];
        }
        return $options;
    }

    function loadMetaData() {
        $metadata_path = $this->settings['metadata'].'/countries.yml';
        $fileName = sprintf($metadata_path);
        return (array) Yaml::parseFile($fileName);
    }

}
