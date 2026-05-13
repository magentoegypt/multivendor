<?php
namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

class CategoriesJson extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
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
     * CategoriesJson constructor.
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
     * Get tree node (Ajax version).
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /*
         * check tree expanded
         */
        if ($this->getRequest()->getParam('expand_all')) {
            $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setIsTreeWasExpanded(true);
        } else {
            $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setIsTreeWasExpanded(false);
        }
        $categoryId = (int) $this->getRequest()->getPost('id');
        if ($categoryId) {
            $this->getRequest()->setParam('id', $categoryId);

            //load category
            $category = $this->_initCategory();
            if (!$category) {
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
            }
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setJsonData(
                $this->layoutFactory->create()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tree')
                    ->getTreeJson($category)
            );
        }
    }
}
