<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vnecoms\Quotation\Block\Customer;

/**
 * Class QuoteList
 * @package Vnecoms\Quotation\Block\Customer
 */
class QuoteList extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $_quoteCollectionFactory;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Vnecoms\Quotation\Model\Source\Status
     */
    protected $_quoteStatus;

    protected $_template = "Vnecoms_Quotation::customer/quotelist.phtml";

    protected $quotes;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Vnecoms\Quotation\Model\QuoteFactory $quoteFactory,
        \Vnecoms\Quotation\Model\Source\Status $quoteStatus,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_quoteCollectionFactory = $quoteCollectionFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_customerSession = $customerSession;
        $this->_quoteStatus = $quoteStatus;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    protected function getLoadedQuoteCollection()
    {
        if(!$this->quotes){
        $customerId = $this->_customerSession->getCustomer()->getId();
        $this->quotes = $this->_quoteCollectionFactory->create()
            ->setOrder('entity_id', 'desc')
            ->setCustomerFilter($customerId)
            ->setStartingFilter();
        }
        
        return $this->quotes;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        $pager = $this->getLayout()->createBlock(
            \Vnecoms\Quotation\Block\Customer\Pager::class,
            'sales.order.history.pager'
        )->setCollection(
            $this->getQuoteCollection()
        );
        $this->setChild('pager', $pager);
        $this->getQuoteCollection()->load();
        
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getQuoteCollection()
    {
        return $this->getLoadedQuoteCollection();
    }

    /**
     * Get quote status label corresponding with value
     * @param int $status
     * @return string|null
     */
    public function getQuoteStatusLabel($status)
    {
        $quoteStatus = 'Null';
        if (!$status) {
            return '';
        }
        $allStatuses = $this->_quoteStatus->getOptions();
        foreach ($allStatuses as $k => $v) {
            if ($k == $status) {
                return $v;
            }
        }
        return $quoteStatus;
    }

    /**
     * Get quote status text label class
     * @param int $status
     * @return string|null
     */
    public function getQuoteStatusTextClass($status)
    {
        $quoteStatus = '';
        if (!$status) {
            return '';
        }
        $allStatuses = $this->_quoteStatus->getOptions();
        foreach ($allStatuses as $k => $v) {
            if ($k == $status) {
                return strtolower($v);
            }
        }
        return $quoteStatus;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * @param int $quoteId
     * @return string
     */
    public function getViewQuoteUrl($quoteId)
    {
        return $this->getUrl('quotation/customer/view', ['quote_id' => $quoteId, '_secure' => true]);
    }

    /**
     * @param int $quoteId
     * @return string
     */
    public function getDeleteQuoteUrl($quoteId)
    {
        return $this->getUrl('quotation/customer/delete', ['quote_id' => $quoteId, '_secure' => true]);
    }
}
