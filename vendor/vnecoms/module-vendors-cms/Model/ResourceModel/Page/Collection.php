<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel\Page;

use Vnecoms\VendorsCms\Api\Data\PageInterface;
use Vnecoms\VendorsCms\Model\ResourceModel\AbstractCollection;

/**
 * CMS page collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'page_id';

    /**
     * Load data for preview flag.
     *
     * @var bool
     */
    protected $_previewFlag;

    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCms\Model\Page', 'Vnecoms\VendorsCms\Model\ResourceModel\Page');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

    /**
     * Returns pairs identifier - title for unique identifiers
     * and pairs identifier|page_id - title for non-unique after first.
     *
     * @return array
     */
    public function toOptionIdArray()
    {
        $res = [];
        $existingIdentifiers = [];
        foreach ($this as $item) {
            $identifier = $item->getData('identifier');

            $data['value'] = $identifier;
            $data['label'] = $item->getData('title');

            if (in_array($identifier, $existingIdentifiers)) {
                $data['value'] .= '|'.$item->getData('page_id');
            } else {
                $existingIdentifiers[] = $identifier;
            }

            $res[] = $data;
        }

        return $res;
    }

    /**
     * Set first store flag.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function setFirstStoreFlag($flag = false)
    {
        $this->_previewFlag = $flag;

        return $this;
    }

    /**
     * Perform operations after collection load.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
        $this->_previewFlag = false;

        return parent::_afterLoad();
    }
}
