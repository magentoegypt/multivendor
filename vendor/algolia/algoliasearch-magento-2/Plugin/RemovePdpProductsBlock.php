<?php

namespace Algolia\AlgoliaSearch\Plugin;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Framework\View\Element\AbstractBlock;

class RemovePdpProductsBlock
{
    const RELATED_BLOCK_NAME = 'catalog.product.related';
    const UPSELL_BLOCK_NAME = 'product.info.upsell';
    /**
     * @var ConfigHelper
     */
    private $_configHelper;

    /**
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->_configHelper = $configHelper;
    }

    /**
     * @param AbstractBlock $subject
     * @param $result
     *
     * @return mixed|string
     */
    public function afterToHtml(AbstractBlock $subject, $result)
    {
        if (($subject->getNameInLayout() === self::RELATED_BLOCK_NAME && $this->_configHelper->isRecommendRelatedProductsEnabled() && $this->_configHelper->isRemoveCoreRelatedProductsBlock()) || ($subject->getNameInLayout() === self::UPSELL_BLOCK_NAME && $this->_configHelper->isRecommendFrequentlyBroughtTogetherEnabled() && $this->_configHelper->isRemoveUpsellProductsBlock())) {
            return '';
        }

        return $result;
    }
}
