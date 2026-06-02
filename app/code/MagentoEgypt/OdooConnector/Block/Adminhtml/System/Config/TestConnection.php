<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders the "Test Connection" button in system configuration.
 */
class TestConnection extends Field
{
    /**
     * @var string
     */
    protected $_template = 'MagentoEgypt_OdooConnector::system/config/test_connection.phtml';

    public function render(AbstractElement $element): string
    {
        $element->unsScope();
        $element->unsCanUseWebsiteValue();
        $element->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('odooconnector/system_config/testConnection');
    }

    public function getButtonHtml(): string
    {
        /** @var Button $button */
        $button = $this->getLayout()->createBlock(Button::class);
        $button->setData([
            'id' => 'odoo_test_connection_button',
            'label' => __('Test Connection'),
        ]);

        return $button->toHtml();
    }
}
