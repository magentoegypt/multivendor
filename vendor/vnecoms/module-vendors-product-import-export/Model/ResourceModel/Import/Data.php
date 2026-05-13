<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import;

/**
 * ImportExport import data resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Data extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Iterator
     */
    protected $_iterator = null;

    /**
     * Helper to encode/decode json
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Vnecoms\VendorsProductImportExport\Helper\Data
     */
    protected $importHelper;

    /**
     * Data constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Vnecoms\VendorsProductImportExport\Helper\Data $importHelper
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Vnecoms\VendorsProductImportExport\Helper\Data $importHelper,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->jsonHelper = $jsonHelper;
        $this->importHelper = $importHelper;
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_product_import_queue', 'queue_id');
    }

    /**
     * Return behavior from import data table.
     *
     * @return string
     */
    public function getBehavior()
    {
        return $this->getUniqueColumnData('behavior');
    }

    /**
     * Return entity type code from import data table.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return $this->getUniqueColumnData('entity');
    }

    /**
     * Return request data from import data table
     *
     * @param string $code parameter name
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUniqueColumnData($code)
    {
        $connection = $this->getConnection();
        $values = array_unique($connection->fetchCol($connection->select()->from($this->getMainTable(), [$code])));

        if (count($values) != 1) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error in data structure: %1 values are mixed', $code)
            );
        }
        return $values[0];
    }

    /**
     * Save import rows bunch.
     *
     * @param int $vendorId
     * * @param string $entity
     * @param string $behavior
     * @param array $data
     * @return int
     */
    public function saveBunch($vendorId, $behavior, array $data)
    {
        $importData = [];
        $utf8Attributes = $this->importHelper->getUtf8Attribute();
        foreach ($data as $row) {
            $sku = $row['sku'];

            foreach ($utf8Attributes as $attribute) {
                if (isset($row[$attribute])) {
                    if (mb_detect_encoding($row[$attribute], 'UTF-8') != "UTF-8") {
                        $row[$attribute] = utf8_encode($row[$attribute]);
                    }
                }
            }

            unset($row['sku']);
            $importData[] = [
                'vendor_id' => $vendorId,
                'behavior' => $behavior,
                'sku' => $sku,
                'product_data' => $this->jsonHelper->jsonEncode($row),
            ];
        }
        $result = $this->getConnection()->insertArray($this->getMainTable(), ['vendor_id','behavior','sku','product_data'], $importData);
        return $result;
    }
}
