<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Checkout\Block\Cart;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Plugin\Checkout\Block\Checkout\AttributeMerger;

class CartLayoutProcessor
{
    /**
     * @var array
     */
    private $orderFields = [];

    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var AttributeMerger
     */
    private $attributeMergerPlugin;

    public function __construct(
        Config $checkoutConfig,
        AttributeMerger $attributeMergerPlugin
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->attributeMergerPlugin = $attributeMergerPlugin;
    }

    private function initOrderFields()
    {
        if (!empty($this->orderFields)) {
            return;
        }

        $fieldConfig = $this->attributeMergerPlugin->getFieldConfig();

        /** @var \Amasty\CheckoutCore\Model\Field $field  */
        foreach ($fieldConfig as $attributeCode => $field) {
            $this->orderFields[$attributeCode] = $field->getData('sort_order');
        }

        if (isset($this->orderFields['region'])) {
            $this->orderFields['region_id'] = $this->orderFields['region'];
        }
    }

    /**
     * @param \Magento\Checkout\Block\Cart\LayoutProcessor $subject
     * @param array $result
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Cart\LayoutProcessor $subject,
        $result
    ) {
        if ($this->checkoutConfig->isEnabled()) {
            $this->initOrderFields();

            $layoutRoot = &$result['components']['block-summary']['children']['block-shipping']
                ['children']['address-fieldsets']['children'];

            foreach ($this->orderFields as $code => $order) {
                if (isset($layoutRoot[$code])) {
                    $layoutRoot[$code]['sortOrder'] = $order;
                }
            }
        }

        return $result;
    }
}
