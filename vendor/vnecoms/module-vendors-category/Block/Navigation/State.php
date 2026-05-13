<?php

namespace Vnecoms\VendorsCategory\Block\Navigation;

use Magento\Framework\View\Element\Template;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class State extends \Magento\LayeredNavigation\Block\Navigation\State
{
    protected $urlFinder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
  //  protected $storeManager;

    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        UrlFinderInterface $urlFinderInterface,
        // \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
    
        parent::__construct($context, $layerResolver, $data);
        $this->urlFinder = $urlFinderInterface;
     //   $this->storeManager = $storeManager;
    }

    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getClearUrl()
    {
        $filterState = [];
        foreach ($this->getActiveFilters() as $item) {
            $filterState[$item->getFilter()->getRequestVar()] = $item->getFilter()->getCleanValue();
        }
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $filterState;
        $params['_escape'] = true;
      //  return $this->_urlBuilder->getUrl('*/*/*', $params);

        if (!$this->getCurrentCategory()) {
            return $this->_urlBuilder->getUrl('*/*/*', $params);
        }
        $rewrite = $this->getRewrite($this->getCurrentCategory()->getId(), $this->_storeManager->getStore()->getId());

        if (!$rewrite) {
            return $this->_urlBuilder->getUrl('*/*/*', $params);
        }

        $url = $this->_urlBuilder->getUrl('vendorscategory/category/view', $params);

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
