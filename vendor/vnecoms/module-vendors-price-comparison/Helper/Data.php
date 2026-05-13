<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PRODUCT_TYPE_RESTRICTION  = 'vendors/pricecomparison/product_type_restriction';
    const XML_ATTRIBUTE_SET_RESTRICTION = 'vendors/pricecomparison/attribute_set_restriction';
    const XML_FILTER_ATTRIBUTES     = 'vendors/pricecomparison/filter_attributes';
    const XML_SHOWING_NUMBER        = 'vendors/pricecomparison/showing_number';
    const XML_SHOW_ADDRESS          = 'vendors/pricecomparison/show_address';
    const XML_SHOW_SALES_COUNT      = 'vendors/pricecomparison/show_sales_count';
    const XML_SHOW_JOINED_DATE      = 'vendors/pricecomparison/show_joined_date';
    const XML_SHOW_COUNTRY_FILTER   = 'vendors/pricecomparison/show_country_filter';
    const XML_MAIN_PRODUCT_TYPE     = 'vendors/pricecomparison/main_product';


    /**
     * @var \Vnecoms\Vendors\Helper\Email
     */
    protected $_emailHelper;

    /**
     * @var array
     */
    protected $_allowedAttributes;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vnecoms\Vendors\Helper\Email $emailHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Vnecoms\Vendors\Helper\Email $emailHelper,
        array $allowedAttributes = []
    ) {
        parent::__construct($context);
        $this->_emailHelper = $emailHelper;
        $this->_allowedAttributes = $allowedAttributes;
    }


    /**
     * Send new product approval notification email to admin.
     * @param \Magento\Catalog\Model\Product $product
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     */
    public function sendNewProductApprovalEmailToAdmin(
        \Magento\Catalog\Model\Product $product,
        \Vnecoms\Vendors\Model\Vendor $vendor
    ) {
        $adminEmail = $this->scopeConfig->getValue(self::XML_CATALOG_ADMIN_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(!$adminEmail) return;
        $adminEmail = str_replace(" ", "", $adminEmail);
        $adminEmail = $adminEmail ? explode(",", $adminEmail) : [];

        $this->_emailHelper->sendTransactionEmail(
            self::XML_CATALOG_NEW_PRODUCT_APPROVAL_EMAIL_ADMIN,
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            self::XML_CATALOG_EMAIL_SENDER,
            $adminEmail,
            ['product'=>$product, 'vendor'=>$vendor]
        );
    }

    /**
     * Get product type restriction
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getProductTypeRestriction(){
        $values = $this->scopeConfig->getValue(self::XML_PRODUCT_TYPE_RESTRICTION);
        return $values ? explode(",",$values) : [];
    }

    /**
     * Get attribute set restriction
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getAttributeSetRestriction(){
        $values = $this->scopeConfig->getValue(self::XML_ATTRIBUTE_SET_RESTRICTION);
        return $values ? explode(",",$values) : [];
    }

    /**
     * Get filter attributes
     *
     * @return multitype:
     */
    public function getFilterAttributes(){
        $values = $this->scopeConfig->getValue(self::XML_FILTER_ATTRIBUTES);
        return $values ? explode(",",$values) : [];
    }

    /**
     * Get number of seller will be showing in price comparison page.
     *
     * @return number:
     */
    public function getShowingNumber(){
        return $this->scopeConfig->getValue(self::XML_SHOWING_NUMBER);
    }

    /**
     * Can show vendor address in price comparison page
     *
     * @return number:
     */
    public function canShowAddress(){
        return (bool)($this->scopeConfig->getValue(self::XML_SHOW_ADDRESS));
    }

    /**
     * Can show vendor's sales count in price comparison page
     *
     * @return number:
     */
    public function canShowSalesCount(){
        return (bool)$this->scopeConfig->getValue(self::XML_SHOW_SALES_COUNT);
    }

    /**
     * Can show vendor joined date in price comparison page
     *
     * @return number:
     */
    public function canShowJoinedDate(){
        return (bool)$this->scopeConfig->getValue(self::XML_SHOW_JOINED_DATE);
    }

    /**
     * Show country filter
     *
     * @return number:
     */
    public function showCountryFilter(){
        return (bool)$this->scopeConfig->getValue(self::XML_SHOW_COUNTRY_FILTER);
    }

    /**
     * Get main product type
     *
     * @return string
     */
    public function getMainProductType(){
        return $this->scopeConfig->getValue(self::XML_MAIN_PRODUCT_TYPE);
    }

    /**
     * Get the list of attributes that allow vendor to enter when select and sell
     * @return multitype:
     */
    public function getAllowedAttribute(){
        return $this->_allowedAttributes;
    }

}
