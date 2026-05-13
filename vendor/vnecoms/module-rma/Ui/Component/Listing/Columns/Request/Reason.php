<?php

namespace Vnecoms\RMA\Ui\Component\Listing\Columns\Request;

use Magento\Framework\Data\OptionSourceInterface;

class Reason implements OptionSourceInterface
{
    /**
     * Reason Object
     * @var \Vnecoms\RMA\Model\Reason
     */
    protected $_reason;

    /**
     * Reason Object
     * @var \Vnecoms\RMA\Model\Reason
     */
    protected $_reason_options;


    public function toOptionArray()
    {
        if (!$this->_reason) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $reason = $om->create('Vnecoms\RMA\Model\Reason')->toOptionArray();
            $isEnableReasonOther = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\Helper\Config'
            )->allowOtherReasons();
            ;
            if ($isEnableReasonOther) {
                $first = ["label"=>__("Other Reason"),"value"=>0];
                array_unshift($reason, $first);
            }


            $this->_reason = $reason;
        }
        return $this->_reason;
    }

    public function getOptionArrayGrid()
    {
        if (!$this->_reason_options) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $reason = $om->create('Vnecoms\RMA\Model\Reason')->getOptionArray();
            $isEnableReasonOther = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\Helper\Config'
            )->allowOtherReasons();
            ;
            if ($isEnableReasonOther) {
                $first = [0 => __("Other Reason")];
                array_unshift($reason, $first);
            }
            $this->_reason_options = $reason;
        }
        return $this->_reason_options;
    }
}
