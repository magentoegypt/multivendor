<?php
/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block;
use Vnecoms\Quotation\Model\Quote;

class Quotepage extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Quote
     */
    protected $_quote;

    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $_session;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Quotation\Model\Session $session,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_session = $session;
        $this->_isScopePrivate = true;
    }



    /**
     * Get current quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = $this->_session->getQuote();
        }
        return $this->_quote;
    }

    /**
     * Get all visible items (parent items)
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        if (!$this->getQuote()) return [];
        return $this->getQuote()->getAllVisibleItems();
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        if (!$this->getQuote()) return [];
        return $this->getQuote()->getTotals();
    }

    /**
     * @return string
     */
    public function getContinueShoppingUrl()
    {
        return $this->getUrl('');
    }

    public function getItemsCount()
    {
        if(!$this->getQuote() || !$this->getQuote()->getId()) return 0;
        return (int) $this->getQuote()->getItemsCount();
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('checkout', ['_secure' => true]);
    }

    /**
     * Get item row html
     *
     * @param   \Vnecoms\Quotation\Model\Item $item
     * @return  string
     */
    public function getItemHtml(\Vnecoms\Quotation\Model\Item $item)
    {
        $renderer = $this->getItemRenderer($item->getProductType())->setItem($item);
        return $renderer->toHtml();
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
            $type = 'default';
        }
        $rendererList = $this->_getRendererList();
        if (!$rendererList) {
            throw new \RuntimeException('Renderer list for block "' . $this->getNameInLayout() . '" is not defined');
        }
        $overriddenTemplates = $this->getOverriddenTemplates() ?: [];
        $template = isset($overriddenTemplates[$type]) ? $overriddenTemplates[$type] : $this->getRendererTemplate();
        return $rendererList->getRenderer($type, 'default', $template);
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
}
