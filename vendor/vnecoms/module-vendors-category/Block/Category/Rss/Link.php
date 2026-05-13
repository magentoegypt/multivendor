<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Category\Rss;

/**
 * Class Link.
 */
class Link extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry = null;

    /**
     * @var \Magento\Framework\App\Rss\UrlBuilderInterface
     */
    protected $rssUrlBuilder;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Rss\UrlBuilderInterface   $rssUrlBuilder
     * @param \Magento\Framework\Registry                      $registry
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Rss\UrlBuilderInterface $rssUrlBuilder,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->rssUrlBuilder = $rssUrlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function isRssAllowed()
    {
        return true;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Subscribe to RSS Feed');
    }

    /**
     * @return string
     */
    protected function getLinkParams()
    {
        return [
            'type' => 'vendor-category',
            'cid' => $this->registry->registry('current_vendor_category')->getId(),
            'store_id' => $this->_storeManager->getStore()->getId(),
        ];
    }

    /**
     * @return bool
     */
    public function isTopCategory()
    {
        return $this->registry->registry('current_vendor_category')->getLevel() == 1;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->rssUrlBuilder->getUrl($this->getLinkParams());
    }
}
