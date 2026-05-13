<?php
namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

class SuggestCategories extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
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
     * SuggestCategories constructor.
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
     * Category list suggestion based on already entered symbols.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setJsonData(
            $this->layoutFactory->create()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tree')
                ->getSuggestedCategoriesJson($this->getRequest()->getParam('label_part'))
        );
    }
}
