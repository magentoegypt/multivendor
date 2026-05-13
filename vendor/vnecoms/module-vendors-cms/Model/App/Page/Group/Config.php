<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group;

class Config
{
    /**
     * Available page types.
     *
     * @var array
     */
    protected $_pageGroup = null;

    /**
     * Data storage.
     *
     * @var \Magento\Framework\Config\DataInterface
     */
    protected $_dataStorage;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     */
    public function __construct(\Magento\Framework\Config\DataInterface $dataStorage)
    {
        $this->_dataStorage = $dataStorage;
    }

    /**
     * Initialize page types list.
     *
     * @return $this
     */
    protected function _initPageGroups()
    {
        if ($this->_pageGroup === null) {
            $this->_pageGroup = [];
            foreach ($this->_dataStorage->get(null) as $pageTypeId => $pageTypeConfig) {
                $pageTypeConfig['label'] = (string) new \Magento\Framework\Phrase($pageTypeConfig['label']);
                $this->_pageGroup[$pageTypeId] = new \Magento\Framework\DataObject($pageTypeConfig);
            }
        }

        return $this;
    }

    /**
     * Retrieve available page groups.
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getPageGroups()
    {
        $this->_initPageGroups();

        return $this->_pageGroup;
    }
}
