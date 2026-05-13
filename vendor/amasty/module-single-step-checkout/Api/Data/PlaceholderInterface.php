<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Api\Data;

interface PlaceholderInterface
{
    public const PLACEHOLDER_ID = 'placeholder_id';
    public const STORE_ID = 'store_id';
    public const PLACEHOLDER = 'placeholder';
    public const ATTRIBUTE_ID = 'attribute_id';

    /**
     * @return int
     */
    public function getPlaceholderId(): int;

    /**
     * @param int $placeholderId
     *
     * @return $this
     */
    public function setPlaceholderId(int $placeholderId): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string|null
     */
    public function getPlaceholder(): ?string;

    /**
     * @param string $placeholder
     *
     * @return $this
     */
    public function setPlaceholder(string $placeholder): self;

    /**
     * @return int
     */
    public function getAttributeId(): int;

    /**
     * @param int $attributeId
     *
     * @return $this
     */
    public function setAttributeId(int $attributeId): self;
}
