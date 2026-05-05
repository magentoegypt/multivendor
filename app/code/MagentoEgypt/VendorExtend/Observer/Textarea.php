<?php 
namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\Event\ObserverInterface;

class Textarea implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $form = $observer->getForm();

        $form->getElement('home_content_fieldset')->removeField('home_content');
        
        $form->getElement('home_content_fieldset')->addField(
            'home_content',
            'textarea',
            [
                'name' => 'theme[home_content]',
                'label' => __('Default Home Content'),
                'title' => __('Default Home Content'),
                'required' => true,
            ],
            'home_layout'
        );

        $data = $observer->getObject()->getData();

        $form->setValues($data);
    }
}
