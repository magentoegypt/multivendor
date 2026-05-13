<?php

namespace Vnecoms\Quotation\Controller\Guest;

use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultInterface;

class View extends Action\Action
{
    /**
     * @var \Vnecoms\Quotation\Helper\Guest
     */
    protected $guestLoaderQuote;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Vnecoms\Quotation\Helper\Guest $guestLoaderQuote
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        \Vnecoms\Quotation\Helper\Guest $guestLoaderQuote,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->guestLoaderQuote = $guestLoaderQuote;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->guestLoaderQuote->loadValidQuote($this->getRequest());
        if ($result instanceof ResultInterface) {
            return $result;
        }
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->setRobots('NOINDEX, NOFOLLOW');
        $this->guestLoaderQuote->getBreadcrumbs($resultPage);
        return $resultPage;
    }
}
