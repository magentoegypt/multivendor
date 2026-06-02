<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Displays the (read-only) inbound callback URL Odoo should POST to.
 */
class CallbackUrl extends Field
{
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $url = $this->storeManager->getStore()->getBaseUrl() . 'odooconnector/inbound/receive';

        return '<p><code>' . $this->escapeHtml($url) . '</code></p>'
            . '<p class="note"><span>' . $this->escapeHtml(
                __('Point Odoo automated actions here (HMAC-signed). The receiving endpoint is added in the inbound phase.')
            ) . '</span></p>';
    }
}
