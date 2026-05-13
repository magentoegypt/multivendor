<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel\Layout;

/**
 * Class Plugin.
 */
class Plugin
{
    /** @var \Vnecoms\VendorsCms\Helper\Data */
    protected $cmsHelperData;
    /**
     * @var \Vnecoms\VendorsCms\Model\ResourceModel\Layout\Update
     */
    private $update;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    public function __construct(
        // \Magento\Widget\Model\ResourceModel\Layout\Update $update,
        \Vnecoms\VendorsCms\Model\ResourceModel\Layout\Update $cmsUpdate,
        \Vnecoms\VendorsCms\Helper\Data $cmsHelperData,
        \Magento\Framework\Registry $registry,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ) {
        $this->update = $cmsUpdate;
        $this->cmsHelperData = $cmsHelperData;
        $this->_coreRegistry = $registry;
        $this->_vendorFactory = $vendorFactory;
    }

    public function aroundGetDbUpdateString(
        \Magento\Framework\View\Model\Layout\Merge $subject,
        \Closure $proceed,
        $handle
    )
    {

        $value = $proceed($handle);
        $value .= $this->update->fetchUpdatesByHandle($handle, $this->getVendorId(), $subject->getTheme(), $subject->getScope());
        return $value;
    }

    /**
     * @return int
     */
    protected function getVendorId()
    {
        $vendor = $this->_coreRegistry->registry('vendor');
        if (!$vendor && $product = $this->_coreRegistry->registry('product')) {
            if ($vendorId = $product->getVendorId()) {
                $vendor = $this->_vendorFactory->create()->load($vendorId);
            }
        }
        if ($vendor) {
            return $vendor->getId();
        }
    }

    public function aroundGetCacheId(
        \Magento\Framework\View\Model\Layout\Merge $subject,
        \Closure $proceed
    ) {
        $value = $proceed();
        $value .= $this->getVendorId();
        return $value;
    }
}
