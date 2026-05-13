<?php

namespace Vnecoms\Vendors\Block\Adminhtml\Form\Element;

use Magento\Framework\App\ObjectManager;

class Editor extends \Magento\Framework\Data\Form\Element\Editor
{
    /**
     * Prepare default SELECT values
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $wysiwygConfig = ObjectManager::getInstance()->create('Magento\Cms\Model\Wysiwyg\Config');
        $wysiwygConfig = $wysiwygConfig->getConfig();
        $wysiwygConfig['add_variables'] = false;
        $wysiwygConfig['add_widgets'] = false;
        $wysiwygConfig['plugins'] = '';
        if($wysiwygConfig instanceof \Magento\Framework\DataObject){
            $settings = $wysiwygConfig->getSettings();
            if(!is_array($settings)){
                $settings = [];
            }
            $settings['plugins'] = '';
            $settings['toolbar1'] = 'formatselect | styleselect | fontsizeselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';
            $wysiwygConfig->setSettings($settings);
        }

        $this->setConfig($wysiwygConfig);
    }
}
