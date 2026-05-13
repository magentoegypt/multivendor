<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCategory\Plugin\Block\Adminhtml\PendingProduct;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Edit
{

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository
     */
    protected $_attributeRepository;


    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;


    /**
     * @var \Vnecoms\VendorsCategory\Model\CategoryFactory
     */
    protected $_vendorCategoryFactory;

    /**
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $attrRepository
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Vnecoms\VendorsCategory\Model\CategoryFactory $vendorCategoryFactory
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Attribute\Repository $attrRepository,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $vendorCategoryFactory
    ) {
        $this->_attributeRepository = $attrRepository;
        $this->_categoryFactory = $categoryFactory;
        $this->_vendorCategoryFactory = $vendorCategoryFactory;
    }
    /**
     * Check if resource for which access is needed has self permissions defined in webapi config.
     *
     * @param \Magento\Framework\Authorization $subject
     * @param callable $proceed
     * @param string $privilege
     *
     * @return bool true If resource permission is self, to allow
     * customer access without further checks in parent method
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductAttributeValue(
        \Vnecoms\VendorsProduct\Block\Adminhtml\PendingProduct\Edit $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product,
        $attributeCode,
        $attributeValue = false
    ) {
        $attribute = $this->_attributeRepository->get($attributeCode);
        $isOptionAttr = sizeof($attribute->getOptions()) > 0;

        if ($attributeValue ===false) {
            if (in_array($attributeCode, ['category_ids','ves_category_ids'])) {
                $attributeValue = $product->getData($attributeCode);
            } else {
                $attributeValue = $isOptionAttr ? $product->getResource()->getAttributeRawValue($product->getId(), $attributeCode, $product->getStoreId()) : $product->getData($attributeCode);
            }
        }

        if ($attributeCode =='category_ids' || $attributeCode =='ves_category_ids') {
            $tmpValue = [];
            if($attributeCode =='category_ids'){
                $categoryCollection = $this->_categoryFactory->create()
                    ->getCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToFilter('entity_id', ['in' => $attributeValue]);

            }else{
                $categoryCollection = $this->_vendorCategoryFactory->create()
                    ->getCollection()
                    ->addFieldToSelect('name')
                    ->addFieldToFilter('category_id', ['in' => $attributeValue]);
            }

            foreach ($categoryCollection as $category) {
                $tmpValue[] = $category->getName();
            }
            $attributeValue = implode(",", $tmpValue);
        } elseif (is_array($attributeValue)) {
            if ($attributeCode == "tier_price") {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $tmpValue = [];

                foreach ($attributeValue as $value) {
                    if ($value["website_id"] == 0) {
                        $websiteName = __("All Websites");
                    } else {
                        $websiteName =  $om->get('Magento\Store\Model\Website')->load($value["website_id"])->getName();
                    }
                    $groupName = $om->get('Magento\Customer\Model\Group')->load($value["cust_group"])->getName();
                    if (!isset($groupName)) {
                        $groupName = __("All Group");
                    }
                    $string = $websiteName." , ";
                    $string .= $groupName." , ";
                    $string .= $value["price_qty"]." , ";
                    $string .= $value["price"]." , ";
                    $tmpValue[] = trim(trim($string), ",")."\n";
                }
                $attributeValue = implode("|", $tmpValue);
            } else {
                $tmpValue = [];
                foreach ($attributeValue as $value) {
                    $tmpValue[] = $attribute->getSource()->getOptionText($value);
                }
                $attributeValue = implode(",", $tmpValue);
            }
        } else {
            $attributeValue = $isOptionAttr?$product->getResource()->getAttribute($attributeCode)->getSource()->getOptionText($attributeValue):$attributeValue;
        }

        return $attributeValue;
    }
}
