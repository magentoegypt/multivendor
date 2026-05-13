<?php
namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg;

use Magento\Framework\Registry;

/**
 * Images manage controller for VendorsCms WYSIWYG editor.
 *
 * @author      VES Team <core@magentocommerce.com>
 */
abstract class Images extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::wysiwyg_images';
    /** @var \Magento\Framework\View\Result\LayoutFactory  */
    protected $resultLayoutFactory;

    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\View\LayoutFactory  */
    protected $layoutFactory;

    /** @var \Magento\Framework\Controller\Result\RawFactory  */
    protected $resultRawFactory;

    /** @var Images  */
    protected $helperImages;

    /**
     * Images constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context              $context
     * @param \Magento\Framework\View\Result\LayoutFactory     $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\LayoutFactory            $layoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory
     * @param \Vnecoms\VendorsCms\Helper\Wysiwyg\Images        $helperImages
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Vnecoms\VendorsCms\Helper\Wysiwyg\Images $helperImages
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->helperImages = $helperImages;
    }


    /**
     * Init storage.
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->getStorage();

        return $this;
    }

    /**
     * Register storage model and return it.
     *
     * @return \Vnecoms\VendorsCms\Model\Wysiwyg\Images\Storage
     */
    public function getStorage()
    {
        if (!$this->_coreRegistry->registry('storage')) {
            $storage = $this->_objectManager->create('Vnecoms\VendorsCms\Model\Wysiwyg\Images\Storage');
            $this->_coreRegistry->register('storage', $storage);
        }

        return $this->_coreRegistry->registry('storage');
    }

}
