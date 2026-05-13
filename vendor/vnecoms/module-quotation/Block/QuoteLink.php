<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;

/**
 * Class AccountLink show in top links dropdown
 * @package Vnecoms\VendorsFavoriteSeller\Block
 */
class QuoteLink extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var HttpContext
     */
    private $httpContext;

    public function __construct(
        Template\Context $context,
        HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->httpContext = $httpContext;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('quotation/customer/index');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return __('My Quotes');
    }

    public function toHtml()
    {
        if (!$this->isCustomerLoggedIn()) {
            return '';
        }

        return '<li class="my-quote-link"><a ' . $this->getLinkAttributes() . ' >' . $this->escapeHtml($this->getLabel()) . '</a></li>';
    }

    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
