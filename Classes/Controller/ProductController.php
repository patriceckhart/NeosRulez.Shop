<?php
namespace NeosRulez\Shop\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations;
use Neos\Fusion\View\FusionView;

/**
 * @Flow\Scope("singleton")
 */
class ProductController extends ActionController
{

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @Flow\Inject
     * @var Neos\ContentRepository\Domain\Service\ContextFactoryInterface
     */
    protected $contextFactory;

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
     * @return void
     */
    public function indexAction() {
        $context = $this->contextFactory->create();
        $products = (new FlowQuery(array($context->getCurrentSiteNode())))->find('[instanceof NeosRulez.Shop:Document.Product]')->context(array('workspaceName' => 'live'))->sort('_index', 'ASC')->get();
//        \Neos\Flow\var_dump($products);
        $this->view->assign('products', $products);
    }

    /**
     * @param array $args
     * @return void
     */
    public function updatePropertyAction(array $args) {
        $nodeIdentifier = '#' . $args['identifier'];
        $context = $this->contextFactory->create();
        $product = (new FlowQuery(array($context->getCurrentSiteNode())))->find($nodeIdentifier)->context(array('workspaceName' => 'live'))->get()[0];
        $product->setProperty($args['property'], $args['value']);
        $this->redirect('index');
    }

}
