<?php
/**
 *
 * Created by Vnecoms Core Team.
 *
 * @category  Vnecoms
 * @package   Vnecoms_ModuleName
 * @author    Vnecoms
 * @created_by mrtuvn
 * @date: 28/04/2017
 * @time: 17:20
 * @copyright Copyright (c) 2012-2017 Vnecoms
 * @license   https://www.vnecoms.com
 */


namespace Vnecoms\VendorsSellerList\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Store\Model\ScopeInterface;

class Data extends ProductList
{

    const XML_PATH_SELLER_PER_PAGE = 'vendors/sellerlist/seller_per_page';
    const XML_PATH_TOP_STATIC_BLOCK = 'vendors/sellerlist/top_static_block';
    const XML_PATH_BOTTOM_STATIC_BLOCK = 'vendors/sellerlist/bottom_static_block';

    /**
     * Returns available mode for view
     *
     * @return array|null
     */
    public function getAvailableViewMode()
    {
        return null;
    }

    /**
     * Get top static block ID
     *
     * @return string
     */
    public function getTopStaticBlockId(){
        return $this->scopeConfig->getValue(self::XML_PATH_TOP_STATIC_BLOCK);
    }

    /**
     * Get bottom static block ID
     *
     * @return string
     */
    public function getBottomStaticBlockId(){
        return $this->scopeConfig->getValue(self::XML_PATH_BOTTOM_STATIC_BLOCK);
    }

    /**
     * @return int
     */
    public function getNumDefaultSeller()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SELLER_PER_PAGE);
    }

    /**
     * @param string $mode
     * @return array|string
     */
    public function getAvailableLimit($viewMode): array
    {
        return [5 => 5,10 => 10,20 => 20,40 => 40];
    }

    /**
     * Retrieve default per page values
     *
     * @param string $viewMode
     * @return string (comma separated)
     */
    public function getDefaultLimitPerPageValue($viewMode): int
    {
            return $this->scopeConfig->getValue(
                self::XML_PATH_SELLER_PER_PAGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }
}
