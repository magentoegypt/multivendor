<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\Raw;
use Vnecoms\Quotation\Api\QuoteRepositoryInterface;
use Vnecoms\Quotation\Model\QuoteFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * Class with class map capability
 *
 * ...
 */
class Delete extends \Magento\Customer\Controller\AbstractAccount
{
    /** @var Session  */
    protected $session;

    /** @var QuoteFactory  */
    protected $quoteFactory;

    /**
     * @var ResultPageFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /** @var QuoteRepositoryInterface  */
    protected $quoteRespository;

    public function __construct(
        Context $context,
        Session $customerSession,
        QuoteFactory $quoteFactory,
        HttpContext $httpContext,
        QuoteRepositoryInterface $quoteRepository
    ) {
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->session = $customerSession;
        $this->quoteFactory = $quoteFactory;
        $this->httpContext = $httpContext;
        $this->quoteRespository = $quoteRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isCustomerLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }

        if ($quoteId) {
            $result = [];
            try {
                $quote = $this->quoteFactory->create();
                if ($quote->load($quoteId)) {
                    $this->quoteRespository->deleteById($quoteId);
                    $result['success'] = true;
                    $this->messageManager->addSuccessMessage('Quote id %1 has been deleted', $quoteId);
                    $resultRedirect->setPath('quotation/customer/index');
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $result['error'] = true;
                $result['message'] = $e->getMessage();
                $resultRedirect->setPath('*/*/');
            }
        }

        return $resultRedirect;
    }

    /**
     * @return mixed|null|bool
     */
    protected function isCustomerLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }
}
