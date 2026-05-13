<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Block\Quote;

use Magento\Store\Model\ScopeInterface;
use Vnecoms\Quotation\Model\Quote;

class Sidebar extends \Magento\Framework\View\Element\Template
{
    /**
     * Block alias fallback
     */
    const DEFAULT_TYPE = 'default';

    /**
     * Xml pah to sidebar display value
     */
    const XML_PATH_QUOTE_SIDEBAR_DISPLAY = 'quotation/sidebar/enabled';

    /**
     * Xml pah to sidebar count value
     */
    const XML_PATH_QUOTE_SIDEBAR_COUNT = 'quotation/sidebar/number';

    /**
     * @var Quote|null
     */
    protected $_quote = null;

    /**
     * @var array
     */
    protected $_totals;

    /**
     * @var array
     */
    protected $_itemRenders = [];

    /**
     * TODO: MAGETWO-34827: unused object?
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var array
     */
    protected $jsLayout;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Customer\CustomerData\JsLayoutDataProviderPoolInterface $jsLayoutDataProvider,
        array $data = []
    ) {
        if (isset($data['jsLayout'])) {
            /* $this->jsLayout = array_merge_recursive($jsLayoutDataProvider->getData(), $data['jsLayout']); */
            $this->jsLayout = $data['jsLayout'];
            unset($data['jsLayout']);
        } else {
            $this->jsLayout = $jsLayoutDataProvider->getData();
        }
        $this->_customerSession = $customerSession;
        $this->session = $session;
        parent::__construct($context, $data);
        $this->imageHelper = $imageHelper;
    }

    /**
     * Returns quote sidebar config
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'quoteUrl' => $this->getQuoteRequestPageUrl(),
            'addQuoteUrl' => $this->getAddQuoteActionUrl(),
            'updateItemQtyUrl' => $this->getUpdateItemQtyUrl(),
            'removeItemUrl' => $this->getRemoveItemUrl(),
            'imageTemplate' => $this->getImageHtmlTemplate(),
            'baseUrl' => $this->getBaseUrl(),
            'maxItemsVisible' => $this->getMaxItemsCount(),
            'websiteId' => $this->_storeManager->getStore()->getWebsiteId(),
        ];
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->session->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Get all cart items
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->getQuote()->getAllVisibleItems();
    }

    public function getAddQuoteActionUrl()
    {
        return $this->getUrl('quotation/quote/add',[]);
    }

    /**
     * Retrieve renderer list
     *
     * @return \Magento\Framework\View\Element\RendererList
     */
    protected function _getRendererList()
    {
        return $this->getRendererListName() ? $this->getLayout()->getBlock(
            $this->getRendererListName()
        ) : $this->getChildBlock(
            'renderer.list'
        );
    }

    /**
     * Retrieve item renderer block
     *
     * @param string|null $type
     * @return \Magento\Framework\View\Element\Template
     * @throws \RuntimeException
     */
    public function getItemRenderer($type = null)
    {
        if ($type === null) {
            $type = self::DEFAULT_TYPE;
        }
        $rendererList = $this->_getRendererList();
        if (!$rendererList) {
            throw new \RuntimeException('Renderer list for block "' . $this->getNameInLayout() . '" is not defined');
        }
        $template = $this->getRendererTemplate();
        return $rendererList->getRenderer($type, self::DEFAULT_TYPE, $template);
    }

    /**
     * @return string
     */
    public function getImageHtmlTemplate()
    {
        return $this->imageHelper->getFrame()
            ? 'Magento_Catalog/product/image'
            : 'Magento_Catalog/product/image_with_borders';
    }

    /**
     * Get quote request page url
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getQuoteRequestPageUrl()
    {
        return $this->getUrl('quotation');
    }

    /**
     * Get quote page url
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getQuoteUrl()
    {
        return $this->getUrl('quotation');
    }

    /**
     * Get update cart item url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getUpdateItemQtyUrl()
    {
        return $this->getUrl('quotation/sidebar/updateItemQty', ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     * Get remove cart item url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRemoveItemUrl()
    {
        return $this->getUrl('quotation/sidebar/removeItem', ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     * Define if Quote Pop-Up Menu enabled
     *
     * @return bool
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsNeedToDisplaySideBar()
    {
        return (bool)$this->_scopeConfig->getValue(
            self::XML_PATH_QUOTE_SIDEBAR_DISPLAY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return totals from custom quote if needed
     *
     * @return array
     */
    public function getTotalsCache()
    {
        if (empty($this->_totals)) {
            $this->_totals = $this->getQuote()->getTotals();
        }
        return $this->_totals;
    }

    /**
     * Return base url.
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Return max visible item count for quote
     *
     * @return int
     */
    private function getMaxItemsCount()
    {
        return (int)$this->_scopeConfig->getValue(self::XML_PATH_QUOTE_SIDEBAR_COUNT, ScopeInterface::SCOPE_STORE);
    }

    public function getRequestUrl()
    {
        return $this->getUrl('quotation/ajax/createQuote');
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getId();
    }
}
