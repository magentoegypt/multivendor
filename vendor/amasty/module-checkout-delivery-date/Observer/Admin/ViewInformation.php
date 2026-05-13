<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Observer\Admin;

use Amasty\CheckoutDeliveryDate\Block\Adminhtml\Sales\Order\Create\Deliverydate;
use Amasty\CheckoutDeliveryDate\Block\Adminhtml\Sales\Order\Delivery;
use Amasty\CheckoutDeliveryDate\Model\ConfigProvider;
use Amasty\CheckoutCore\Model\Config as CheckoutConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ViewInformation implements ObserverInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CheckoutConfig
     */
    private $checkoutConfig;

    public function __construct(
        ConfigProvider $configProvider,
        CheckoutConfig $checkoutConfig
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutConfig = $checkoutConfig;
    }

    /**
     * 'core_layout_render_element' event
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return;
        }

        $elementName = $observer->getElementName();
        $transport = $observer->getTransport();
        $html = $transport->getOutput();
        $block = $observer->getLayout()->getBlock($elementName);
        $blockName = null;
        $checkDeliveryEnable = false;
        $flagName = 'amcheckout_delivery_' . $elementName;

        switch ($elementName) {
            case 'order_info':
                $blockName = Delivery::class;
                break;
            case 'form_account':
                $blockName = Deliverydate::class;
                $checkDeliveryEnable = true;
                break;
        }

        if (empty($blockName)
            || ($checkDeliveryEnable && !$this->configProvider->isDeliveryDateEnabled())
            || $block->hasData($flagName)
        ) {
            return;
        }

        $deliveryBlock = $observer->getLayout()->createBlock($blockName);
        $html .= $deliveryBlock->toHtml();
        $block->setData($flagName, true);
        $transport->setOutput($html);
    }
}
