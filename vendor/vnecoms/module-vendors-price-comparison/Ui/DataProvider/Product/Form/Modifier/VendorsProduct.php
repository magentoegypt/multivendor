<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Vnecoms\VendorsProduct\Helper\Data as VendorProductHelper;
use Vnecoms\VendorsPriceComparison\Helper\Data as PriceComparisonHelper;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use \Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Registry;
use Vnecoms\Vendors\Model\Session;

/**
 * Data provider for "Customizable Options" panel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VendorsProduct extends AbstractModifier
{
    const KEY_SUBMIT_URL = 'submit_url';
    const KEY_VALIDATE_URL = 'validate_url';
    
    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_vendorProductHelper;
    
    /**
     * Set collection factory
     *
     * @var AttributeSetCollectionFactory
     */
    protected $_attributeSetCollectionFactory;
    
    /**
     * @var LocatorInterface
     */
    protected $locator;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * System event manager
     *
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    
    /**
     * @var ArrayManager
     */
    protected $arrayManager;
    
    /**
     * @var Registry
     */
    protected $_coreRegistry;
    
    /**
     * @var array
     */
    protected $productUrls = [
        self::KEY_SUBMIT_URL => 'pricecomparison/selectandsell/save',
        self::KEY_VALIDATE_URL => 'pricecomparison/selectandsell/validate',
    ];

    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $_priceComparisonHelper;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $session;
    
    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param VendorProductHelper $vendorProductHelper
     * @param EventManager $eventManager
     * @param AttributeSetCollectionFactory $attributeSetCollectionFactory
     * @param ArrayManager $arrayManager
     * @param Registry $coreRegistry
     * @param PriceComparisonHelper $priceComparisonHelper
     * @param Session $session
     * @return \Vnecoms\VendorsPriceComparison\Ui\DataProvider\Product\Form\Modifier\VendorsProduct
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        VendorProductHelper $vendorProductHelper,
        EventManager $eventManager,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
        ArrayManager $arrayManager,
        Registry $coreRegistry,
        PriceComparisonHelper $priceComparisonHelper,
        Session $session
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->_vendorProductHelper = $vendorProductHelper;
        $this->_attributeSetCollectionFactory = $attributeSetCollectionFactory;
        $this->_eventManager = $eventManager;
        $this->arrayManager = $arrayManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_priceComparisonHelper = $priceComparisonHelper;
        $this->session = $session;
        return $this;
    }
    /**
     * @var array
     */
    protected $_meta = [];
    

    public function modifyData(array $data){
        if(!$this->_coreRegistry->registry('select_and_sell')) return $data;
        
        $model = $this->locator->getProduct();
        $attributeSetId = $model->getAttributeSetId();

        $parameters = [
            'id' => $model->getId(),
            'type' => $model->getTypeId(),
            'store' => $model->getStoreId(),
        ];
        $actionParameters = array_merge($parameters, ['set' => $attributeSetId]);

        $submitUrl = $this->urlBuilder->getUrl($this->productUrls[self::KEY_SUBMIT_URL], $actionParameters);
        $validateUrl = $this->urlBuilder->getUrl($this->productUrls[self::KEY_VALIDATE_URL], $actionParameters);
        
        if(isset($data[$model->getId()]['configurable-matrix'])){
            $matrixData = $data[$model->getId()]['configurable-matrix'];
            $vendor = $this->session->getVendor();
            foreach($matrixData as $key=>$variant){
                $matrixData[$key]['id'] = null;
                $matrixData[$key]['product_link'] = null;
                $matrixData[$key]['canEdit'] = 1;
                $matrixData[$key]['newProduct'] = 1;
                $matrixData[$key]['sku'] = $variant['sku'].'-'.$vendor->getVendorId();
            }
            $data[$model->getId()]['configurable-matrix'] = $matrixData;
        }
        return array_replace_recursive(
            $data,
            [
                'config' => [
                    self::KEY_SUBMIT_URL => $submitUrl,
                    self::KEY_VALIDATE_URL => $validateUrl,
                ]
            ]
        );
        return $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        if(!$this->locator->getProduct()->getData('select_from_product_id')) return $meta;
        $this->_meta = $meta;
        $this->removeNotUsedSections();
        $this->removeNotUsedAttributes();
        $this->removeImportOptions();
        return $this->_meta;
    }
    
    /**
     * Remove not used sections
     */
    public function removeNotUsedSections(){
        $allowedSections = [
            'product-details',
            'search-engine-optimization',
            'custom_options',
            'sources',
            'import_options_modal',
            'advanced_pricing_modal',
            'configurable',
            'configurable_associated_product_modal',
            'configurable_attribute_set_handler_modal',
        ];
        $transport = new \Magento\Framework\DataObject([
            'allowed_sections' => $allowedSections,
        ]);
        
        $this->_eventManager->dispatch('selectandsell_remove_not_used_sections',['transport' => $transport]);
        $allowedSections = $transport->getAllowedSections();
        
        foreach(array_keys($this->_meta) as $section){
            if(
                !in_array($section, $allowedSections) &&
                isset($this->_meta[$section])
            ) {
                unset($this->_meta[$section]);
            }
        }
    }
    
    /**
     * Remove not used attributes
     */
    public function removeNotUsedAttributes(){
        $allowedAttributes = $this->_priceComparisonHelper->getAllowedAttribute();
        
        /*Don't remove any fields in these groups*/
        $excludeGroup =[
            'custom_options',
            'import_options_modal',
            'advanced_pricing_modal',
            'configurable',
            'configurable_associated_product_modal',
            'configurable_attribute_set_handler_modal',
        ];
        
        $transport = new \Magento\Framework\DataObject([
            'allowed_attributes' => $allowedAttributes,
            'allowed_groups' => $excludeGroup,
        ]);
        
        $this->_eventManager->dispatch('selectandsell_remove_not_used_attributes',['transport' => $transport]);
        
        $allowedAttributes = $transport->getAllowedAttributes();
        $excludeGroup = $transport->getAllowedGroups();
        
        foreach($this->_meta as $groupCode=>$group){
            if(
                !isset($group['children']) ||
                in_array($groupCode, $excludeGroup)
            ) continue;
            
            $attributeContainers = $group['children'];
            foreach($attributeContainers as $key=>$container){
                $attrCode = str_replace("container_", "", $key);
                if(!in_array($attrCode, $allowedAttributes)){
                    unset($this->_meta[$groupCode]['children'][$key]);
                }
            }
        }
    }
    
    /**
     * Remove import option button
     */
    public function removeImportOptions(){
        $path = $this->arrayManager->findPath('button_import', $this->_meta);
        $this->_meta = $this->arrayManager->remove($path, $this->_meta);
        return $this;
    }

    
}
