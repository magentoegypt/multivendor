<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block;

class Import extends \Vnecoms\Vendors\Block\Vendors\Widget\Container
{
    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;
    
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper,
        array $data = []
    ) {
        $this->_productHelper = $productHelper;
        parent::__construct($context, $data);
    }
    
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Vnecoms_VendorsProductImportExport';
        $this->_controller = 'import';
        $this->_headerText = __('Manage Import Queue');
        $this->_addButtonLabel = __('Import Product');
        
        parent::_construct();
        $this->addButton('import', [
            'id' => 'import_product',
            'label' => __('Import Product'),
            'class' => 'bg-purple btn-lg fa fa-cloud-upload',
            'button_class' => '',
            'onclick' => "setLocation('" . $this->getImportUrl() . "')",
        ]);
        $this->addButton('start_queue', [
            'id' => 'start_qwueue',
            'label' => __('Start Queue'),
            'class' => 'btn-primary btn-lg fa  fa-rocket',
            'button_class' => '',
            'onclick' => "setLocation('" . $this->getStartQueueUrl() . "')",
        ]);
    }

    /**
     * Get Import URL
     *
     * @return string
     */
    public function getImportUrl()
    {
        return $this->getUrl('catalog/import/form');
    }
    
    /**
     * Get Import URL
     *
     * @return string
     */
    public function getStartQueueUrl()
    {
        return $this->getUrl('catalog/import/startQueue');
    }
    
    /**
     * Is update product approval
     */
    public function isUpdateProductsApproval()
    {
        return $this->_productHelper->isUpdateProductsApproval();
    }
}
