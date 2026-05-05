<?php
namespace MagentoEgypt\VendorExtend\Block\Widget;

use Vnecoms\Vendors\Model\Vendor;

class Seller extends \Vnecoms\VendorsSellerList\Block\SellerList implements \Magento\Widget\Block\BlockInterface
{
    protected $vendorCollectionFactory;

    public function getConfig($key, $default = '')
    {
        if ($this->hasData($key)) {
            return $this->getData($key);
        }
        return $default;
    }

    public function _toHtml()
    {
        $template = $this->getConfig('template');
        $this->setTemplate($template);
        return parent::_toHtml();
    }

    public function getSellerCollection()
    {
        $sellerIds = $this->getConfig('seller_ids') ?? "";
        $collection = $this->vendorCollectionFactory->create()->addFieldToFilter('status', Vendor::STATUS_APPROVED);
        $sellerIds = explode(',', $sellerIds);
        $sellerIds = array_filter($sellerIds);
        if (!empty($sellerIds)) {
            $collection->addFieldToFilter('vendor_id', array('in' => $sellerIds));
        } else {
            $collection->addFieldToFilter('is_home', 1);
        }
        $collection->setPageSize($this->getConfig('items') ?? 15);
        return $collection;
    }
}