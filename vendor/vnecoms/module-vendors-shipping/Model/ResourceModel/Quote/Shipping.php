<?php

namespace Vnecoms\VendorsShipping\Model\ResourceModel\Quote;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Shipping extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('quote_vendors_shipping', 'entity_id');
    }

    /**
     * @param $vendorId
     * @param $quoteId
     * @param $shippingAmount
     * @param $shippingMethod
     * @param $shippingDes
     * @return $this
     */
    public function saveVendorShippingQuote(
        $vendorId,
        $quoteId,
        $shippingAmount,
        $shippingMethod,
        $shippingDes
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $table,
            'entity_id'
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'quote_id = :quote_id'
        );
        $bind = [
            'vendor_id' => $vendorId,
            'quote_id' => $quoteId
        ];
        $entityId = $connection->fetchOne($select, $bind);

        if(!$entityId){

            $connection->insert($table,  [
                'vendor_id' => $vendorId,
                'quote_id' => $quoteId,
                'shipping_amount' => $shippingAmount,
                'shipping_description' => $shippingDes,
                'shipping_method' => $shippingMethod,
            ]);

        } else {
            $connection->update(
                $table,
                [
                    'shipping_amount' => $shippingAmount,
                    'shipping_description' => $shippingDes,
                    'shipping_method' => $shippingMethod,
                    'discount_shipping_amount' => 0
                ],
                ["entity_id = ?" => $entityId]
            );
        }
        return $this;
    }

    /**
     * @param $vendorId
     * @param $quoteId
     * @param $discountAmount
     * @return $this
     */
    public function updateDiscountVendorShippingQuote(
        $vendorId,
        $quoteId,
        $discountAmount
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $table,
            'entity_id'
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'quote_id = :quote_id'
        );
        $bind = [
            'vendor_id' => $vendorId,
            'quote_id' => $quoteId
        ];
        $entityId = $connection->fetchOne($select, $bind);

        if($entityId){
            $connection->update(
                $table,
                [
                    'discount_shipping_amount' => $discountAmount
                ],
                ["entity_id = ?" => $entityId]
            );
        }
        return $this;
    }

    /**
     * @param $entityId
     * @param $discountAmount
     * @return $this
     */
    public function updateDiscountVendorShippingQuoteById(
        $entityId,
        $discountAmount
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $connection->update(
            $table,
            [
                'discount_shipping_amount' => $discountAmount
            ],
            ["entity_id = ?" => $entityId]
        );
        return $this;
    }

    /**
     * @param $quoteId
     * @return $this
     */
    public function resetDiscountVendorShippingQuote(
        $quoteId
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $connection->update(
            $table,
            [
                'discount_shipping_amount' => 0
            ],
            ["quote_id = ?" => $quoteId]
        );
        return $this;
    }

    /**
     * @param $vendorId
     * @param $quoteId
     * @return mixed
     */
    public function getShippingQuoteByVendor(
        $vendorId,
        $quoteId
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $table
        )->where(
            'vendor_id = :vendor_id'
        )->where(
            'quote_id = :quote_id'
        );
        $bind = [
            'vendor_id' => $vendorId,
            'quote_id' => $quoteId
        ];
        return $connection->fetchRow($select, $bind);
    }

    /**
     * @param $quoteId
     * @return mixed
     */
    public function getAllShippingVendorByQuote(
        $quoteId
    ){
        $table = $this->getTable('quote_vendors_shipping');
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $table
        )->where(
            'quote_id = :quote_id'
        );
        $bind = [
            'quote_id' => $quoteId
        ];
        return $connection->fetchAll($select, $bind);
    }
}
