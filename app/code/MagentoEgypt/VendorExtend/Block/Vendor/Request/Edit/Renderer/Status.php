<?php
namespace MagentoEgypt\VendorExtend\Block\Vendor\Request\Edit\Renderer;

use Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Renderer\Status as BaseStatus;

class Status extends BaseStatus
{
    protected $_storeId;

    /**
     * get Status Title
     * @return $title
     */
    public function getStatusTitle($status) {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $object = $object_manager->get('\Vnecoms\RMA\Model\Status')->load($status);
        return $object->getLabelByStoreId($this->getStoreId());
    }

    protected function getStoreId()
    {
        if($this->_storeId === null) {
            $this->_storeId = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('\Magento\Store\Model\StoreManagerInterface')
                ->getStore()
                ->getId();
        }
        return $this->_storeId;
    }
}
