<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class CalculatorFactory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var array
     */
    protected $classByType = [];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\VendorsCoupon\Helper\Data $helperData
     */
    public function __construct(
      \Magento\Framework\ObjectManagerInterface $objectManager,
      \Vnecoms\VendorsCoupon\Helper\Data $helperData
    )
    {
        $this->classByType = $helperData->getDiscountActions();
        $this->_objectManager = $objectManager;
    }

    /**
     * @param string $type
     * @return \Magento\SalesRule\Model\Rule\Action\Discount\DiscountInterface
     * @throws \InvalidArgumentException
     */
    public function create($type)
    {
        if (!isset($this->classByType[$type])) {
            throw new \InvalidArgumentException($type . ' is unknown type');
        }

        return $this->_objectManager->create($this->classByType[$type]['class']);
    }
}
