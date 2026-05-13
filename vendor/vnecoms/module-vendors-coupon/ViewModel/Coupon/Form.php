<?php
namespace Vnecoms\VendorsCoupon\ViewModel\Coupon;

class Form implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Vnecoms\VendorsCoupon\Helper\Data $helperData
     */
    protected $_helperData;

    /**
     * Constructs a coupon read service object.
     *
     * @param \Vnecoms\VendorsCoupon\Helper\Data $helperData
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Helper\Data $helperData
    ) {
        $this->_helperData = $helperData;
    }

    /**
     * get list Action
     * @return array
     */
    public function getListAction()
    {
      return $this->_helperData->getDiscountActions();
    }
}
