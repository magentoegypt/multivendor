<?php

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class Add extends \Magento\Checkout\Controller\Cart\Add
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
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository
     * @param \Vnecoms\Quotation\Api\Data\QuoteInterface $quote
     * @param \Vnecoms\Quotation\Model\Session $session
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository,
        \Vnecoms\Quotation\Api\Data\QuoteInterface $quote,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quote = $quote;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart, $productRepository);
    }

    /**
     * add quote to quote action
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $response->setData(['error' => true, 'message' => __('The form key is invalid. Please refresh the page.')]);
            $this->resultJsonFactory->create()->setJsonData($response->toJson());
        }
        $params = $this->getRequest()->getParams();
        try {
            $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );

            if (isset($params['qty'])) {
                $params['qty'] = $filter->filter((string)$params['qty']);
            }

            $params = new \Magento\Framework\DataObject($params);

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The product it not available.'));
            }
            if(!$product->getData('ves_enable_quote')){
                throw new \Magento\Framework\Exception\LocalizedException(__('The product "%1" is not allowed to add to quote.', $product->getName()));
            }
            $this->session->addProduct($product, $params);

            //recollect totals
            $this->session->getQuote()->collectTotals();

            //save to session
            $this->quoteRepository->save($this->session->getQuote());
            $this->session->setQuoteId($this->session->getQuote()->getId());

            $this->_eventManager->dispatch(
                'quote_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            $this->messageManager->addSuccessMessage(__(
                'You added %1 to your quote.',
                $product->getName()
            ));
            $response->setData(['error' => false]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response->setData(['error' => true, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $response->setData(['error' => true, 'message' => __('We can\'t add this item to your quote right now.')]);
        }
        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
