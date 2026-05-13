<?php

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Vnecoms\Quotation\Model\Quote;

class Submit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\Quotation\Api\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Vnecoms\Quotation\Api\Data\QuoteInterface
     */
    protected $quote;

    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository
     * @param \Vnecoms\Quotation\Model\Session $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository,
        \Vnecoms\Quotation\Model\Session $session
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->session = $session;
        parent::__construct($context);
    }

    /**
     * add quote to quote action
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $quote = $this->session->getQuote();
            $params = $this->getRequest()->getParams();
            $quote->addData($params);
            $this->quoteRepository->submit($quote);
            
            $this->session->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId());
            
            $this->_redirect('quotation/quote/success');

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $messages = array_unique(explode("\n", $e->getMessage()));
            foreach ($messages as $message) {
                $this->messageManager->addError(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
                );
            }

            return $this->_redirect('quotation');

        } catch (\Exception $e) {
           // echo $e->getMessage();
            $this->messageManager->addException($e, __('We can\'t add this item to your quote right now.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return $this->_redirect('quotation');
        }
    }
}
