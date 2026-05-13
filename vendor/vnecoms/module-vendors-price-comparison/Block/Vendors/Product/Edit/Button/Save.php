<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Block\Vendors\Product\Edit\Button;

use Vnecoms\VendorsProductConfigurable\Block\Vendors\Product\Edit\Button\Save as DefaultSave;

/**
 * Class Save
 */
class Save extends DefaultSave
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $registry = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Registry');
        if(
            !$registry->registry('select_and_sell')
        ) return parent::getButtonData();

        if ($this->getProduct()->isReadonly()) {
            return [];
        }

        return [
            'label' => __('Save Product'),
            'class' => 'save primary btn btn-default btn-success btn-sm',
            'button_class' => 'fa fa-check-circle button-save',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => $this->getSaveTarget(),
                                'actionName' => $this->getSaveAction(),
                                'params' => [
                                    true,
                                    [
                                        'savedraft' => true,
                                        'back' => 'edit'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'class_name' => 'Vnecoms\Vendors\Block\Vendors\Widget\Button\SplitButton',
            'options' => $this->getOptions(),
        ];
    }
    /**
     * Retrieve options
     *
     * @return array
     */
    protected function getOptions()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $registry = $om->get('Magento\Framework\Registry');
        if(
            !$registry->registry('select_and_sell')
        ) return parent::getOptions();

        $helperData =  $om->create('Vnecoms\VendorsProduct\Helper\Data');
        $options = [];
        if(
            ($helperData->isUpdateProductsApproval() && $this->getProduct()->getId()) ||
            ($helperData->isNewProductsApproval() && !$this->getProduct()->getId())
        ){
            $options[] = [
                'id_hard' => 'save_and_submit',
                'label' => __('Save & Submit for Approval'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->getSaveTarget(),
                                    'actionName' => $this->getSaveAction(),
                                    'params' => [
                                        true,
                                        [
                                            'back' => 'edit'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ];
        }

        return $options;
    }


}
