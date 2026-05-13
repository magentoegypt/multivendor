<?php
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Edit\Renderer;

class Status extends \Vnecoms\RMA\Block\Adminhtml\Request\Edit\Renderer\Status
{

    /**
     * get Prefix Html
     * @param $type
     * @return string
     */
    public function getPrefixChangeBy($type)
    {
        $prefix="[A]";
        switch ($type) {
            case \Vnecoms\RMA\Model\Source\Message\Type::CHANGE_BY_DEPARTMENT:
                $prefix= '<span style="color:green">'.__("[Admin]").'</span>';
                break;
            case \Vnecoms\RMA\Model\Source\Message\Type::CHANGE_BY_CUSTOMER:
                $prefix= '<span style="color:red">'.__("[Customer]").'</span>';
                break;
            case \Vnecoms\VendorsRMA\Model\Source\Message\Type::CHANGE_BY_VENDOR:
                $prefix= '<span style="color:blue">'.__("[Vendor]").'</span>';
                break;
        }
        return $prefix;
    }

}
