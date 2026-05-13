<?php

namespace Vnecoms\Quotation\Model\Backend;

use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Session\ValidatorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Adminhtml quote session
 *
 * @method int getQuoteId()
 * @method Session setQuoteId($id)
 * @method Session setCustomerId($id)
 * @method int getCustomerId()
 * @method bool hasCustomerId()
 * @method Session setStoreId($storeId)
 * @method int getStoreId()
 * @method Session setCurrencyId($currencyId)
 * @method string getCurrencyId()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Session extends \Magento\Framework\Session\SessionManager
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;

    /**
     * @var \Vnecoms\Quotation\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Vnecoms\Quotation\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    protected $quoteRepository;


    public function __construct
    (
        \Magento\Framework\App\Request\Http $request,
        SidResolverInterface $sidResolver,
        ConfigInterface $sessionConfig,
        SaveHandlerInterface $saveHandler,
        ValidatorInterface $validator,
        StorageInterface $storage,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\State $appState,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Quotation\Model\QuoteFactory $quoteFactory,
        \Vnecoms\Quotation\Model\QuoteRepository $quoteRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->_storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($request, $sidResolver, $sessionConfig, $saveHandler, $validator, $storage, $cookieManager, $cookieMetadataFactory, $appState);
        if ($this->_storeManager->hasSingleStore()) {
            $this->setStoreId($this->_storeManager->getStore(true)->getId());
        }
    }

    /**
     * @return \Vnecoms\Quotation\Model\Quote
     */
    public function getQuote()
    {
        if ($this->_quote === null) {
            $this->_quote = $this->quoteFactory->create();
            if ($this->getStoreId()) {
                if (!$this->getQuoteId()) {
                    $this->_quote->setIsActive(false);
                    $this->_quote->setStoreId($this->getStoreId());
                    $this->quoteRepository->save($this->_quote);
                    $this->setQuoteId($this->_quote->getId());
                    $this->_quote = $this->quoteRepository->getById($this->getQuoteId());
                } else {
                    $this->_quote = $this->quoteRepository->getById($this->getQuoteId());
                    $this->_quote->setStoreId($this->getStoreId());
                }

                if ($this->getCustomerId() && $this->getCustomerId() != $this->_quote->getCustomerId()) {
                    $customer = $this->customerRepository->getById($this->getCustomerId());
                    $this->_quote->setCustomer($customer);
                    $this->quoteRepository->save($this->_quote);
                }
            }

            $this->_quote->setCurrencyCode($this->getStore()->getCurrentCurrencyCode())
                ->setQuoteCurrencyCode($this->getStore()->getCurrentCurrencyCode());
        }

        return $this->_quote;
    }

    /**
     * Retrieve store model object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        if ($this->_store === null) {
            $this->_store = $this->_storeManager->getStore($this->getStoreId());
            $currencyId = $this->getCurrencyId();
            if ($currencyId) {
                $this->_store->setCurrentCurrencyCode($currencyId);
            }
        }
        return $this->_store;
    }

    public function addItems(array $items)
    {
        foreach ($items as $productId => $config) {
            $config['qty'] = isset($config['qty']) ? (double)$config['qty'] : 1;
            try {
                $this->addItem($productId, $config);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                throw new \Exception($e);
            } catch (\Exception $e) {
                return $e;
            }
        }

        return $this;
    }

    public function updateQuoteItems(array $items)
    {
        foreach ($items as $productId => $config) {

        }
    }

    /**
     * @return ObjectManager
     */
    public function getOm()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * @param $product
     * @param int $config
     */
    public function addItem($product ,$config = 1)
    {
        if (!is_array($config) && !$config instanceof \Magento\Framework\DataObject) {
            $config = ['qty' => $config];
        }
        $config = new \Magento\Framework\DataObject($config);

        //load product
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            $productId = $product;
            $product = $this->getOm()->create(
                'Magento\Catalog\Model\Product'
            )->setStore(
                $this->getStore()
            )->setStoreId(
                $this->getStoreId()
            )->load(
                $product
            );
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We could not add a product to quote by the ID "%1".', $productId)
                );
            }
        }

        //add to quote
        $this->getQuote()->addProduct($product, $config);
    }

    public function removeItem($itemId)
    {

    }

    public function collectTotals()
    {

    }
}