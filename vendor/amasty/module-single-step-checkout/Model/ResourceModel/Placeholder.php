<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\ResourceModel;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Placeholder extends AbstractDb
{
    public const MAIN_TABLE = 'amasty_amcheckout_placeholder';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'placeholder_id');
    }

    /**
     * @param $placeholderEntity
     * @param int $attributeId
     * @param int $storeId
     *
     * @return PlaceholderInterface
     * @throws LocalizedException
     */
    public function loadByAttributeIdAndStoreId(
        $placeholderEntity,
        int $attributeId,
        int $storeId
    ): PlaceholderInterface {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('attribute_id = ?', $attributeId)->where('store_id = ?', $storeId);
        $data = $this->getConnection()->fetchRow($select);
        if ($data) {
            $placeholderEntity->addData($data);
        }

        return $placeholderEntity;
    }
}
