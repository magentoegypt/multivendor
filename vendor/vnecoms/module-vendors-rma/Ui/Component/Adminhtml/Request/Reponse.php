<?php

namespace Vnecoms\VendorsRMA\Ui\Component\Adminhtml\Request;
use Magento\Framework\Data\OptionSourceInterface;

class Reponse implements OptionSourceInterface
{
    /**
     * Department Object
     * @var \Vnecoms\HelpDesk\Model\Template
     */
    protected $_template;

    /**
     * Get Template
     *
     * @return \Vnecoms\HelpDesk\Model\Template
     */
    public function getTemplates(){

        if(!$this->_template){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $vendor = $om->create('Vnecoms\Vendors\Model\Session');
            $this->_template = $om->create('Vnecoms\RMA\Model\Reponse')
                ->getCollection()
                ->addFieldToFilter('status',array('eq'=>1))
                ->addFieldToFilter('vendor_id',null);
        }
        return $this->_template;
    }


    public function toOptionArray()
    {

        $data=array();
        $templates= $this->getTemplates();

        $data[] =  array(
            "label"=> __("----- No Template  -----"),
            "value"=> ""
        );

        foreach ($templates as $template){
            $data[]= array(
                "label"=> $template->getData('title'),
                "value"=> $template->getData('reponse_id')
            );
        }
        return $data;

    }

    public function getOptionArrayGrid()
    {
        $data=array();
        $templates= $this->getTemplates();
        foreach ($templates as $template){
            $data[$template->getData('reponse_id')]=  $template->getData('title');
        }
        return $data;

    }
}


