<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;

class Item extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Item');
    }

    /**
     * get All Item RMA by order item Id
     * @param $item_id
     * @return mixed
     */
    public function getCollectionByItemId($item_id)
    {
        $collection = $this->getCollection()->addFieldToFilter("order_item_id", $item_id);
        return $collection;
    }


    public function getViewAllRmaByItemId($item_id, $request_id)
    {
        $collections = $this->getCollectionByItemId($item_id);
        $data =[];
        $data["qty"] = 0;
        $data["item_id"] = 0;
        $data["rma"] = [];
        foreach ($collections as $item) {
            $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\Model\Request'
            )->load($item->getRequestId());
            if (!$request->getId() || $request_id == $request->getId()) {
                $data["qty"] = $item->getQty();
                $data["item_id"]= $item->getItemId();
                continue;
            }
            $data["rma"][] = [
                "request_id"=>$request->getId(),
                "request_increment_id"=> $request->getIncrementId()
            ];
        }
        return $data;
    }

    public function getAllRmaByItemId($item_id)
    {
        $collections = $this->getCollectionByItemId($item_id);
        $data =[];
        $data["qty"] = 0;
        $data["rma"] = [];
        foreach ($collections as $item) {
            $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\Model\Request'
            )->load($item->getRequestId());
            if (!$request->getId()) {
                continue;
            }
            $qty = $request->getState() != \Vnecoms\RMA\Model\Request::STATE_CANCELED ? $item->getQty() : 0;
            $data["rma"][] = [
              "request_id"=>$request->getId(),
              "request_increment_id"=> $request->getIncrementId()
            ];
            $data["qty"] += $qty;
        }
        return $data;
    }

    public function getAllRmaByItemIdWithOutCurrentRequest($item_id, $request_id)
    {
        $collections = $this->getCollectionByItemId($item_id);
        $data =[];
        $data["qty"] = 0;
        $data["rma"] = [];
        foreach ($collections as $item) {
            $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\Model\Request'
            )->load($item->getRequestId());
            if (!$request->getId() || $request->getId() == $request_id) {
                continue;
            }
            $qty = $request->getState() != \Vnecoms\RMA\Model\Request::STATE_CANCELED ? $item->getQty() : 0;
            $data["rma"][] = [
                "request_id"=>$request->getId(),
                "request_increment_id"=> $request->getIncrementId()
            ];
            $data["qty"] += $qty;
        }
        return $data;
    }
}
