<?php

namespace Vnecoms\VendorsCategory\Block\Product\ProductList;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Pager extends \Magento\Theme\Block\Html\Pager
{
    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
   // protected $storeManager;

    /**
     * Pager constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param UrlFinderInterface $urlFinder
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        //    \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        array $data = []
    ) {
       // $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        parent::__construct($context, $data);
    }

    public function getPagerUrl($params = [])
    {
        $urlParams = [];
        $urlParams['_current'] = true;
        $urlParams['_escape'] = true;
        $urlParams['_use_rewrite'] = true;
        $urlParams['_fragment'] = $this->getFragment();
        $urlParams['_query'] = $params;

        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->_storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->getUrl($this->getPath(), $urlParams);
        }

        $url = $this->getUrl($this->getPath(), $urlParams);

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
