<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Widget\Columns;

/**
 * Form fieldset renderer
 */
class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Renders grid column
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $value = $this->_getValue($row);
        $rma =  \Magento\Framework\App\ObjectManager::getInstance()->get(
            '\Vnecoms\RMA\Model\Request'
        )->load($row->getData("entity_id"));
        return "<span class='".$this->getStatusClass($rma->getStatusObject())."'>".$rma->getStatusTitle()."</span>";
    }

    /**
     * get Type class
     * @return mixed
     */
    public function getStatusClass($status)
    {
        $class = "data-grid-cell-content status status-".$status->getCode();
        return $class;
    }
}
