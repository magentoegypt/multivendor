<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * "SAVE 29%" on the product page.
 *
 * Figma prints the saving beside the price as an accent chip. Computed from the
 * price model rather than from special_price, for the same reason the card badge
 * is: special_price alone ignores catalog price rules and tier prices, so a chip
 * derived from it can disagree with the price printed next to it.
 *
 * Returns nothing when there is no saving, so the chip cannot invent one.
 */
class ProductSavings implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    public function getProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');

        return $product instanceof Product ? $product : null;
    }

    public function getPercent(): int
    {
        [$regular, $final] = $this->prices();
        if ($regular <= 0 || $final <= 0 || $final >= $regular) {
            return 0;
        }

        return (int) round((1 - $final / $regular) * 100);
    }

    public function getAmount(): ?string
    {
        [$regular, $final] = $this->prices();
        if ($regular <= 0 || $final <= 0 || $final >= $regular) {
            return null;
        }

        return $this->priceCurrency->format($regular - $final, false);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function prices(): array
    {
        $product = $this->getProduct();
        if (!$product) {
            return [0.0, 0.0];
        }

        try {
            $info = $product->getPriceInfo();

            return [
                (float) $info->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue(),
                (float) $info->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue(),
            ];
        } catch (\Throwable $e) {
            return [0.0, 0.0];
        }
    }
}
