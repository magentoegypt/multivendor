<?php
namespace MagentoEgypt\VendorExtend\Model\Source;

use Vnecoms\Vendors\Model\Vendor;

class SellerList implements \Magento\Framework\Option\ArrayInterface
{
    protected $vendorCollectionFactory;

    public function __construct(\Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $vendorCollectionFactory)
    {
        $this->vendorCollectionFactory = $vendorCollectionFactory;
    }

    public function toOptionArray()
    {
        $sellerCollection = $this->vendorCollectionFactory->create()->addFieldToFilter('status', Vendor::STATUS_APPROVED);
        $sellerCollection->joinTable(
            ['vendor_config' => $sellerCollection->getTable('ves_vendor_config')],
            'vendor_id=entity_id',
            ['vendor_title' => 'value'],
            'vendor_config.path like "general/store_information/name"',
            'left'
        );

        $options = [];

        foreach($sellerCollection as $seller) {
            $options[] = [
                'label' => $seller->getVendorId(),
                'value' => $seller->getVendorId()
            ];
        }
        return $options;
    }
}