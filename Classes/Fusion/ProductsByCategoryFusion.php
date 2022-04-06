<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class ProductsByCategoryFusion extends AbstractFusionObject {

    /**
     * @Flow\Inject
     * @var \Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * @return array
     */
    public function evaluate():array
    {
        $identifier = $this->fusionValue('identifier');
        $result = [];
        $result['count'] = 0;
        $result['items'] = [];
        if($identifier) {
            $context = $this->contextFactory->create();
            $products = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Product]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
            if(!empty($products)) {
                foreach ($products as $product) {
                    $categories = $product->getProperty('categories');
                    if(!empty($categories)) {
                        foreach ($categories as $category) {
                            if($category->getIdentifier() == $identifier) {
                                $result['count'] = $result['count'] + 1;
                                $result['items'][] = $product;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

}
