<?php

namespace Vnecoms\RMA\Ui\Component\Request;

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
    public function getTemplates()
    {
        if (!$this->_template) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_template = $om->create('Vnecoms\RMA\Model\Reponse')
                ->getCollection()
                ->addFieldToFilter('status', ['eq'=>1]);
        }
        return $this->_template;
    }


    public function toOptionArray()
    {

        $data=[];
        $templates= $this->getTemplates();

        $data[] =  [
            "label"=> __("----- No Template  -----"),
            "value"=> ""
        ];

        foreach ($templates as $template) {
            $data[]= [
                "label"=> $template->getData('title'),
                "value"=> $template->getData('reponse_id')
            ];
        }
        return $data;
    }

    public function getOptionArrayGrid()
    {
        $data=[];
        $templates= $this->getTemplates();
        foreach ($templates as $template) {
            $data[$template->getData('reponse_id')]=  $template->getData('title');
        }
        return $data;
    }
}
