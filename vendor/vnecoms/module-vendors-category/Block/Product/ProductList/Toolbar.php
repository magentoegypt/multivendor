<?php

namespace Vnecoms\VendorsCategory\Block\Product\ProductList;

use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
   // protected $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        // \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        array $data = []
    ) {
     //   $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
    }

    public function getPagerUrl($params = [])
    {
        $urlParams = [];
        $urlParams['_current'] = true;
        $urlParams['_escape'] = false;
        $urlParams['_use_rewrite'] = true;
        $urlParams['_query'] = $params;

        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->_storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->getUrl('*/*/*', $urlParams);
        }

        $url = $this->getUrl('*/*/*', $urlParams);

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
