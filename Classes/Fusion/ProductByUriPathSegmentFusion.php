<?php
namespace NeosRulez\Shop\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;

class ProductByUriPathSegmentFusion extends AbstractFusionObject {

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
        $uriPathSegment = $this->fusionValue('uriPathSegment');
        $result = [];
        if($uriPathSegment) {
            $context = $this->contextFactory->create();
            $product = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Product]')->context(array('workspaceName' => 'live'))->filter('[uriPathSegment *= ' . $uriPathSegment . ']')->sort('_index', 'ASC')->get(0);
            $result = [
                'item' => $product,
                'cart' => (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Cart]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get(0)
            ];
        }
        return $result;
    }

}
