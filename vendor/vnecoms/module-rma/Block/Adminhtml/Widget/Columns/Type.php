<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Widget\Columns;

/**
 * Form fieldset renderer
 */
class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
        $title =  \Magento\Framework\App\ObjectManager::getInstance()->get(
            '\Vnecoms\RMA\Ui\Component\Grid\Request\Type'
        )->getLableByCode($value);
        return "<span class='".$this->getTypeClass($value)."'>".$title."</span>";
    }

    /**
     * get Type class
     * @return mixed
     */
    public function getTypeClass($prority)
    {
        $class = "";
        switch ($prority) {
            case "replace":
                $class= "type type-replace";
                break;
            case "refund":
                $class= "type type-refund";
                break;
        }
        return $class;
    }
}
