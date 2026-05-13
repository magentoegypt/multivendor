<?php
namespace Vnecoms\VendorsProduct\Model\Plugin;


class ElasticLayerPlugin
{

    /**
     * Vendor helper
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorHelper;

    /**
     * Vendor Product helper
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $productHelper;

    /**
     * ElasticLayerPlugin constructor.
     * @param \Vnecoms\Vendors\Helper\Data $helper
     * @param \Vnecoms\VendorsProduct\Helper\Data $productHelper
     */
    public function __construct(
        \Vnecoms\Vendors\Helper\Data $helper,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper
    ) {
        $this->vendorHelper = $helper;
        $this->productHelper = $productHelper;
        return $this;
    }

    /**
     * @param $subject
     * @param $query
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeQuery($subject,$query) {
        // Add the entity_id filter to the Elastic collection
        if (!$this->vendorHelper->moduleEnabled()) {
            return [$query];
        }
        $query['body']['query']['bool']['must'][]['terms'] = ['approval' => $this->productHelper->getAllowedApprovalStatus()];
        return [$query];

    }
}
