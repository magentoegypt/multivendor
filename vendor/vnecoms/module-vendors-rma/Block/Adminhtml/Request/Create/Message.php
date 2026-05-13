<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 20/07/2016
 * Time: 15:53
 */
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Create;

class Message extends \Vnecoms\RMA\Block\Adminhtml\Request\Create\Message
{

    /**
     * get Template
     * @return array
     */
    public function getTemplateOptions(){
        $data = array();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $reponse = $object_manager->get('\Vnecoms\VendorsRMA\Ui\Component\Adminhtml\Request\Reponse');
        $data =  $reponse->getOptionArrayGrid();
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


}
