<?php

namespace Vnecoms\Quotation\Block\Quote;

use Magento\Customer\Model\Context;

/**
 * Quote Submit Success Page
 *
 */
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $quoteSession;


    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Vnecoms\Quotation\Api\QuoteRepositoryInterface
     */
    protected $quoteRepository;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Session $quoteSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Quotation\Model\Session $quoteSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->quoteRepository = $quoteRepository;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $quoteId = $this->quoteSession->getLastQuoteId();
        $quote = $this->quoteRepository->getById($quoteId);
        $this->addData(
            [
                'view_quote_url' => $this->getUrl(
                    'quotation/customer/view/',
                    ['quote_id' => $quote->getEntityId()]
                ),
 
                'can_view_quote'  => $this->canViewQuote($quote),
                'quote_id'  => $quote->getIncrementId()
            ]
        );
    }
    
    /**
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @return boolean
     */
    public function canViewQuote(\Vnecoms\Quotation\Model\Quote $quote){
        return $this->customerSession->isLoggedIn() && $this->customerSession->getCustomerId() == $quote->getCustomerId();
    }


    /**
     * @return string
     * @since 100.2.0
     */
    public function getContinueUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
