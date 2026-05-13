<?php
namespace Vnecoms\Quotation\Block\Adminhtml\Quote;

class Create extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_Quotation';
        $this->_controller = 'adminhtml_quote';
        $this->_mode = 'create';
        
        parent::_construct();
        $this->updateButton('save', 'label', __('Continue'));        

        return $this;
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('New Quote');
    }
    
    /**
     * Get back url
     * @see \Magento\Backend\Block\Widget\Form\Container::getBackUrl()
     */
    public function getBackUrl(){
        return $this->getUrl('quotation/quote');
    }
}
