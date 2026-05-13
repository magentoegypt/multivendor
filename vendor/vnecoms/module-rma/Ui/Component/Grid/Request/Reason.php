<?php

namespace Vnecoms\RMA\Ui\Component\Grid\Request;

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
            $this->_reason = $om->create('Vnecoms\RMA\Model\Reason')->toOptionArray();
        }
        return $this->_reason;
    }

    public function getOptionArrayGrid()
    {
        if (!$this->_reason_options) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_reason_options = $om->create('Vnecoms\RMA\Model\Reason')->getOptionArray();
        }
        return $this->_reason_options;
    }
}
