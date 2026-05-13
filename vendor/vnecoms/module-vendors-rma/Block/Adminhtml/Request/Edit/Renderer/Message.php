<?php
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Edit\Renderer;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element;
use Magento\Framework\Registry;

class Message extends \Vnecoms\RMA\Block\Adminhtml\Request\Edit\Renderer\Message
{

    /**
     * get Template
     * @return array
     */
    public function getTemplateOptions(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $status = $object_manager->get('\Vnecoms\VendorsRMA\Ui\Component\Adminhtml\Request\Reponse');
        $data =  $status->getOptionArrayGrid();
        return $data;
    }


    /**
     * get Template
     * @return array
     */
    public function getTemplateJson(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $objectTemplate = $object_manager->get('\Vnecoms\VendorsRMA\Ui\Component\Adminhtml\Request\Reponse');
        $templates =  $objectTemplate->getTemplates();
        return json_encode($templates->getData());
    }

    /**
     * check is show button reply
     * @return bool
     */
    public function isShowButtonReply()
    {
        $state = $this->getRequestRma()->getState() ;
        if (
            $state == \Vnecoms\RMA\Model\Request::STATE_OPEN ||
            $state == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING ||
            $state == \Vnecoms\VendorsRMA\Model\Request::STATE_BEING
        ) {
            return true;
        }
        return false;
    }

}
