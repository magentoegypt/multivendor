<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Blocks;

use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\Result\RawFactory;

//class WysiwygPlugin extends \Magento\Variable\Controller\Adminhtml\System\Variable
class WysiwygPlugin extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::index';
    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->resultRawFactory = $resultRawFactory;

    }

    /**
     * WYSIWYG Plugin Action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
//        $customBlocks = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Block')
//            ->getBlocksOptionArray(true);
//
//        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
//        $resultJson = $this->resultJsonFactory->create();
//
//        return $resultJson->setData([$customBlocks]);

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $this->layoutFactory->create();

        $uniqId = $this->getRequest()->getParam('uniq_id');
        $pagesGrid = $layout->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\Block\Chooser',
            '',
            ['data' => ['id' => $uniqId]]
        );

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($pagesGrid->toHtml());
        return $resultRaw;
    }
}
