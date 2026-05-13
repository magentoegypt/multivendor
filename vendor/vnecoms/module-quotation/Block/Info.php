<?php

namespace Vnecoms\Quotation\Block;

use Magento\Framework\View\Element\Template;
use \Vnecoms\Quotation\Model\Source\Status as QuoteStatus;

class Info extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'customer/quote/info.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Vnecoms\Quotation\Model\Source\Status
     */
    protected $quoteStatus;
    
    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param QuoteStatus $quoteStatus
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        \Magento\Framework\Registry $registry,
        QuoteStatus $quoteStatus,
        array $data = []
    ) {
        $this->quoteStatus = $quoteStatus;
        $this->_isScopePrivate = true;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Quote # %1', $this->getQuote()->getIncrementId()));
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        return $this->coreRegistry->registry('current_quote');
    }
    
    /**
     * @param unknown $status
     * @return Ambigous <string, multitype:number \Magento\Framework\Phrase >
     */
    public function getQuoteStatusLabel($status){
        $quoteStatues = $this->quoteStatus->getOptions();
        return isset($quoteStatues[$status])? $quoteStatues[$status]: '';
    }
}