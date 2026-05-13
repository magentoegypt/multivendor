<?php
/**
 *
 * Created by Vnecoms Core Team.
 *
 * @category  Vnecoms
 * @package   Vnecoms_ModuleName
 * @author    Vnecoms
 * @created_by mrtuvn
 * @date: 06/05/2017
 * @time: 11:22
 * @copyright Copyright (c) 2012-2017 Vnecoms
 * @license   https://www.vnecoms.com
 */


namespace Vnecoms\VendorsSellerList\Block\Seller;


use Magento\Framework\View\Element\Template;

class Search extends \Magento\Framework\View\Element\Template
{
    protected $sellerListHelper;

    public function __construct(
        Template\Context $context,
        \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper,
        array $data = []
    ){
        $this->sellerListHelper = $sellerListHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return bool|null
     */
    public function sellerAjaxSearchEnabled()
    {
        return $this->sellerListHelper->isEnableAjaxSearch();
    }

    public function getImageLoader()
    {
        return $this->getViewFileUrl('images/loader-1.gif');
    }

    /**
     * Retrieve search delay in miliseconds (500 by default)
     *
     * @return int
     */
    public function getSearchDelay()
    {
        return $this->sellerListHelper->getSearchDelay();
    }

    public function getSearchResultUrl()
    {
        return $this->getUrl('sellerlist/ajaxsearch/seller');
    }

    /**
     * Get search query
     * @return \Magento\Framework\App\mixed
     */
    public function getSearchQuery(){
        $searchText = $this->getRequest()->getParam('seller_query');
        return $searchText ? trim($searchText) : '';
    }
}