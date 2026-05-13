<?php

namespace Vnecoms\Quotation\Controller;

use Magento\Framework\App\Action\Context;

/**
 * Class Quote
 * @package Vnecoms\Quotation\Controller
 */
abstract class Quote extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;


    /**
     * @param Context $context
     * @param \Vnecoms\Quotation\Model\Session $session
     */
    public function __construct
    (
        Context $context,
        \Vnecoms\Quotation\Model\Session $session
    ) {
        parent::__construct($context);
        $this->session = $session;
    }

    public function getQuote()
    {
        return $this->getSession()->getQuote();
    }

    public function getSession()
    {
        return $this->session;
    }
}