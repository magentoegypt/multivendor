<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\LayoutProcessor;

use Magento\Framework\App\ProductMetadataInterface;

class SortFields
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param array[] $fields
     * @return void
     * @see \Amasty\CheckoutCore\Model\Field\Form\SortFields
     */
    public function execute(array &$fields): void
    {
        $sortingDirection = $this->getSortingDirection();

        uksort($fields, static function (string $firstKey, string $secondKey) use ($fields, $sortingDirection) {
            $firstField = $fields[$firstKey];
            $secondField = $fields[$secondKey];

            $firstSortOrder = $firstField['sortOrder'] ?? 0;
            $secondSortOrder = $secondField['sortOrder'] ?? 0;

            $diff = $firstSortOrder <=> $secondSortOrder;
            return $diff !== 0 ? $diff : strcmp($firstKey, $secondKey) * $sortingDirection;
        });
    }

    /**
     * Temporary solution: workaround for differences in mageUtils implementation
     * between Magento 2.4.4 and earlier versions.
     *
     * @return int
     */
    private function getSortingDirection(): int
    {
        return version_compare($this->productMetadata->getVersion(), '2.4.4', '>=') ? 1 : -1;
    }
}
