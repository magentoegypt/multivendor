<?php

namespace Vnecoms\RMA\Ui\Component\Grid\Request;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Reason Object
     * @var \Vnecoms\RMA\Model\Status
     */
    protected $_status;

    /**
     * Reason Object
     * @var \Vnecoms\RMA\Model\Status
     */
    protected $_status_options;


    public function toOptionArray()
    {
        if (!$this->_status) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_status = $om->create('Vnecoms\RMA\Model\Status')->toOptionArray();
        }
        return $this->_status;
    }

    public function getOptionArrayGrid()
    {
        if (!$this->_status_options) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_status_options = $om->create('Vnecoms\RMA\Model\Status')->getOptionArray();
        }
        return $this->_status_options;
    }
}
