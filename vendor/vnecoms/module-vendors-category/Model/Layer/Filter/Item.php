<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

/**
 * Filter item model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsCategory\Model\Layer\Filter;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{

    protected $urlFinder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        UrlFinderInterface $urlFinderInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data=[]
    )
    {
        parent::__construct($url, $htmlPagerBlock, $data);
        $this->urlFinder = $urlFinderInterface;
        $this->storeManager = $storeManager;
    }

    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        $query = [
            $this->getFilter()->getRequestVar() => $this->getValue(),
            // exclude current page from urls
            $this->_htmlPagerBlock->getPageVarName() => null,
        ];


        if(!$this->getCurrentCategory()) return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);

        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
        }

        $url = $this->_url->getUrl('vendorscategory/category/view', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);

        $requestPath = $rewrite->getRequestPath();
        $targetPath = $rewrite->getTargetPath();
        $url = str_replace($targetPath, $requestPath, $url);

        return $url;
    }

    /**
     * Get url for remove item from filter
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        $query = [$this->getFilter()->getRequestVar() => $this->getFilter()->getResetValue()];
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $query;
        $params['_escape'] = true;

        if(!$this->getCurrentCategory()) return $this->_url->getUrl('*/*/*', $params);

        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->_url->getUrl('*/*/*', $params);
        }

        $url = $this->_url->getUrl('vendorscategory/category/view', $params);

        $requestPath = $rewrite->getRequestPath();
        $targetPath = $rewrite->getTargetPath();
        $url = str_replace($targetPath, $requestPath, $url);

        return $url;
    }

    /**
     * Get url for "clear" link
     *
     * @return false|string
     */
    public function getClearLinkUrl()
    {
        $clearLinkText = $this->getFilter()->getClearLinkText();
        if (!$clearLinkText) {
            return false;
        }

        $urlParams = [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => [$this->getFilter()->getRequestVar() => null],
            '_escape' => true,
        ];
        if(!$this->getCurrentCategory()) return $this->_url->getUrl('*/*/*', $urlParams);

        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->_url->getUrl('*/*/*', $urlParams);
        }

        $url = $this->_url->getUrl('vendorscategory/category/view', $urlParams);

        $requestPath = $rewrite->getRequestPath();
        $targetPath = $rewrite->getTargetPath();
        $url = str_replace($targetPath, $requestPath, $url);

        return $url;
    }

    /**
     * @param $categoryId
     * @param $storeId
     *
     * @return UrlRewrite|null
     */
    protected function getRewrite($categoryId, $storeId)
    {
        return $this->urlFinder->findOneByData([
            UrlRewrite::ENTITY_ID => $categoryId,
            UrlRewrite::STORE_ID => $storeId,
            UrlRewrite::ENTITY_TYPE => 'vendor_category',
        ]);
    }

    public function getCurrentCategory()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('current_vendor_category');
    }
}
