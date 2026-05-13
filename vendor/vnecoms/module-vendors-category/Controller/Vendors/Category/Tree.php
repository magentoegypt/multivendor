<?php
namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

use Vnecoms\VendorsCategory\Model\Category;

class Tree extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * Tree constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory            $layoutFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Tree Action
     * Retrieve category tree.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $categoryId = (int) $this->getRequest()->getParam('id');

        if (!$categoryId) {
            $rootId = \Magento\Framework\App\ObjectManager::getInstance()->create(
                'Vnecoms\VendorsCategory\Model\Category'
            )->getRootId($this->getVendor()->getId());
            $this->getRequest()->setParam('id', $rootId);
        }
        /*
         * root category always is a category with id = 0,name root, level 0
         */
        $category = $this->_initCategory(true);
        if (!$category) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

        $block = $this->layoutFactory->create()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tree');
        $root = $block->getRoot();
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData([
            'data' => $block->getTree(),
            'parameters' => [
                'text' => $block->buildNodeName($root),
                'draggable' => false,
                'allowDrop' => (bool) $root->getIsVisible(),
                'allowDrop' => true,
                'id' => (int) $root->getId(),
                'expanded' => (int) $block->getIsWasExpanded(),
                'category_id' => (int) $category->getId(),
                'root_visible' => (int) $root->getIsVisible(),
            ],
        ]);
    }
}
