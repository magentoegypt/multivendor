<?php

namespace Vnecoms\VendorsSms\Block\Adminhtml\System\Config\Form\Field\Credit;

class Field extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'system/config/credit.phtml';

    /**
     * Sort values.
     *
     * @param array $data
     *
     * @return array
     */
    protected function _sortValues($data)
    {
        usort($data, [$this, '_sortCreditPackages']);

        return $data;
    }

    /**
     * Sort tier price values callback method.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _sortCreditPackages($a, $b)
    {
        if ($a['tier'] != $b['tier']) {
            return $a['tier'] < $b['tier'] ? -1 : 1;
        }

        return 0;
    }

    /**
     * Prepare global layout
     * Add "Add tier" button to layout.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'label' => __('Add Package'),
                'onclick' => 'return smsCreditControl.addItem()',
                'class' => 'action-default primary',
            ]
        );
        $button->setName('add_credit_package_item_button');

        $this->setChild('add_button', $button);

        return parent::_prepareLayout();
    }

    /**
     * Prepare group price values.
     *
     * @return array
     */
    public function getValues()
    {
        $values = [];
        $values = $this->getElement()->getValue();
        if (!$values) return [];
        $values = unserialize($values);

        return $values ? $values : [];
    }

    /**
     * Retrieve 'add group price item' button HTML.
     *
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
    
    /**
     * Get Base Currency Code
     * 
     * @return string
     */
    public function getBaseCurrencyCode(){
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }
}
