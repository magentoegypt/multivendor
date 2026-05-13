<?php
/**
 * Cms Template Filter Provider.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

/**
 * Filter provider model.
 */
class FilterProvider
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Vnecoms\VendorsCms\Model\Filter\Config
     */
    protected $configData;

    /**
     * @var array
     */
    protected $_instanceList;

    /**
     * FilterProvider constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Vnecoms\VendorsCms\Model\Filter\Config   $configData
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Vnecoms\VendorsCms\Model\Filter\Config $configData
    ) {
        $this->_objectManager = $objectManager;
        $this->configData = $configData;
    }

    /**
     * @param string $instanceName
     *
     * @return \Magento\Framework\Filter\Template
     *
     * @throws \Exception
     */
    protected function _getFilterInstance($instanceName)
    {
        if (!isset($this->_instanceList[$instanceName])) {
            $instance = $this->_objectManager->get($instanceName);

            if (!$instance instanceof \Magento\Framework\Filter\Template) {
                throw new \Exception('Template filter '.$instanceName.' does not implement required interface');
            }
            $this->_instanceList[$instanceName] = $instance;
        }

        return $this->_instanceList[$instanceName];
    }

    /**
     * @param string $name
     *
     * @return \Magento\Framework\Filter\Template
     */
    public function getFilterInstance($name)
    {
        return $this->_getFilterInstance($name);
    }

    /**
     * @return \array[]
     */
    public function getFilters()
    {
        return $this->configData->getFilters();
    }

    /**
     * @param string $id
     *
     * @return \Magento\Framework\Filter\Template
     */
    public function getFilter($id)
    {
        // Check all filter here
        return $this->getFilterInstance($id);
    }

    /**
     * Get Note Filters.
     *
     * @return array
     */
    public function getFilterNotes()
    {
        $notes = [];
        foreach ($this->getFilters() as $filterId => $filter) {
            $notes[$filterId] = $filter['note'];
        }

        return $notes;
    }
}
