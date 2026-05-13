<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Bundle\Block\Catalog\Product\View\Type\Bundle;

use Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option;

class OptionPlugin
{
    /**
     * Fix fatal on our checkout for magento 2.3.2 and 2.2.9
     * 'Call to a member function renderTierPrice() on null',
     * when bundle product in cart. Because catalog_product_view_type_bundle.xml set
     * argument tier_price_renderer, but on our checkout we dont use this layout.
     *
     * @param Option $subject
     */
    public function beforeGetData(
        Option $subject
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if (class_exists(\Magento\Bundle\Block\DataProviders\OptionPriceRenderer::class)) {
            $optionPriceRenderer = $objectManager->get(\Magento\Bundle\Block\DataProviders\OptionPriceRenderer::class);
            $subject->setTierPriceRenderer($optionPriceRenderer);
        }
    }
}
