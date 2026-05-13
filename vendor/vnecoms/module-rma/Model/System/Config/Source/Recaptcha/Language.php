<?php


namespace Vnecoms\RMA\Model\System\Config\Source\Recaptcha;

class Language implements \Magento\Framework\Option\ArrayInterface
{


    
    /**
     * Options getterRussian
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'en', 'label' => __('English')],
            ['value' => 'nl', 'label' => __('Dutch')],
            ['value' => 'fr', 'label' => __('French')],
            ['value' => 'de', 'label' => __('German')],
            ['value' => 'pt', 'label' => __('Portuguese')],
            ['value' => 'ru', 'label' => __('Russian')],
            ['value' => 'es', 'label' => __('Spanish')],
            ['value' => 'tr', 'label' => __('Turkish')],
        ];
    }
}
