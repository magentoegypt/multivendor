<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCredit\Block\Vendors\Order\Creditmemo\Totals;


class Credit extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Data
     */
    protected $helperData;

    /**
     * Credit constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\VendorsCredit\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCredit\Helper\Data $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->helperData = $helperData;
    }

    /**
     * Retrieve credit memo model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->_coreRegistry->registry('current_creditmemo');
    }


    /**
     * get Credit value that allow admin to refund.
     * @return float
     */
    public function getCreditValue()
    {
        return $this->getCreditmemo()->getGrandTotal() + abs($this->getCreditmemo()->getCreditAmount());
    }

    /**
     * Display the block only for registered customer.
     * @see \Magento\Framework\View\Element\Template::_toHtml()
     */
    protected function _toHtml()
    {
        if (!$this->getCreditmemo()->getOrder()->getCustomerId()
            || !$this->helperData->isAllowRefundCredit()
        ) {
            return '';
        }

        return parent::_toHtml();
    }
}
