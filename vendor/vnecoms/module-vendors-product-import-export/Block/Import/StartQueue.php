<?php
/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block\Import;

use Vnecoms\VendorsProductImportExport\Model\Import\DataFactory as ImportDataFactory;

class StartQueue extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\CollectionFactory
     */
    protected $_importDataFactory;

    /**
     * @var array|\Magento\Checkout\Block\Checkout\LayoutProcessorInterface[]
     */
    protected $layoutProcessors;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ImportDataFactory $importDataFactory,
        \Vnecoms\Vendors\Model\Session $session,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
        $this->_importDataFactory = $importDataFactory;
        $this->_vendorSession = $session;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $collection = $this->_importDataFactory->create()->getCollection();
        $collection->addFieldToFilter('vendor_id', $this->_vendorSession->getVendor()->getId())
            ->addFieldToFilter('status', ['in' =>[
                \Vnecoms\VendorsProductImportExport\Model\Import\Data::STATUS_DRAFT
            ]]);
        $this->jsLayout['components']['import']['total_rows'] = $collection->count();
        $this->jsLayout['components']['import']['import_url'] = $this->getImportUrl();

        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return \Laminas\Json\Json::encode($this->jsLayout);
    }

    /**
     * Get import URL
     * @return string
     */
    public function getImportUrl()
    {
        return $this->getUrl('catalog/import/run');
    }
}
