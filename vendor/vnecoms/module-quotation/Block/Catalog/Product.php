<?php

namespace Vnecoms\Quotation\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class AddToQuote
 *
 * ...
 */
class Product extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    public function __construct(
        Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Vnecoms\Quotation\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->productRepository = $productRepository;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->urlEncoder = $urlEncoder;
        $this->helper = $helper;
    }

    /**
     * Get product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        if (!$this->_coreRegistry->registry('product') && $this->getProductId()) {
            $product = $this->productRepository->getById($this->getProductId());
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }
    /**
     * Get add to quote URL
     * @param array $additional
     * @return string
     */
    public function getAddToQuoteUrl($additional = [])
    {
        $product = $this->getProduct();
        $addUrlKey = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;
        $addUrlValue = $this->_urlBuilder->getUrl('*/*/*', ['_use_rewrite' => true, '_current' => true]);
        $additional[$addUrlKey] = $this->urlEncoder->encode($addUrlValue);

        $continueUrl = $this->urlEncoder->encode($this->_urlBuilder->getCurrentUrl());
        $urlParamName = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;

        $routeParams = [
            $urlParamName => $continueUrl,
            'product' => $product->getEntityId(),
            '_secure' => $this->getRequest()->isSecure()
        ];

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_scope_to_url'] = true;
        }

        return $this->_urlBuilder->getUrl('quotation/quote/add', $routeParams);
    }

    /**
     * Is enabled add to cart
     * 
     * @return boolean
     */
    public function isEnabledAddToCart(){
        return (bool) $this->getProduct()->getData('ves_enable_order');
    }
    
    /**
     * Is Enabled add to quote
     * 
     * @return boolean
     */
    public function isEnabledAddToQuote(){
        return (bool) $this->getProduct()->getData('ves_enable_quote');
    }

}
