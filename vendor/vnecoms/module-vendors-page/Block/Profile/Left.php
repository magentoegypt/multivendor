<?php
namespace Vnecoms\VendorsPage\Block\Profile;

use Vnecoms\VendorsPage\Model\Source\ProfilePosition as Profile;

/**
 * Class View
 * @package Magento\Catalog\Block\Category
 */
class Left extends \Vnecoms\Vendors\Block\Profile
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        \Magento\Framework\Registry $registry,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase,
        \Vnecoms\Vendors\Helper\Image $imageHelper,
        \Vnecoms\VendorsSales\Model\ResourceModel\OrderFactory $orderResourceFactory,
        \Magento\Cms\Model\Template\Filter $filter,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        array $data = []
    ) {
        $this->_pageHelper = $pageHelper;
        parent::__construct(
            $context,
            $vendorHelper,
            $configHelper,
            $registry,
            $fileStorageDatabase,
            $imageHelper,
            $orderResourceFactory,
            $filter,
            $vendorFactory,
            $data
        );
    }
    
    protected function _toHtml()
    {
        if ($this->_pageHelper->getProfileBlockPosition() != Profile::POSITION_LEFT) {
            return '';
        }
        return parent::_toHtml();
    }
}
