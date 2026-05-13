<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model;

use Amasty\Checkout\Api\Data\PlaceholderInterface;
use Amasty\Checkout\Model\ResourceModel\Placeholder as ResourcePlaceholder;
use Magento\Framework\Model\AbstractModel;

class Placeholder extends AbstractModel implements PlaceholderInterface
{
    protected function _construct()
    {
        $this->_init(ResourcePlaceholder::class);
    }

    /**
     * @return int
     */
    public function getPlaceholderId(): int
    {
        return (int)$this->getData(self::PLACEHOLDER_ID);
    }

    /**
     * @param int $placeholderId
     *
     * @return PlaceholderInterface
     */
    public function setPlaceholderId(int $placeholderId): PlaceholderInterface
    {
        $this->setData(self::PLACEHOLDER_ID, $placeholderId);

        return $this;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @param int $storeId
     *
     * @return PlaceholderInterface
     */
    public function setStoreId(int $storeId): PlaceholderInterface
    {
        $this->setData(self::STORE_ID, $storeId);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return $this->getData(self::PLACEHOLDER);
    }

    /**
     * @param string $placeholder
     *
     * @return PlaceholderInterface
     */
    public function setPlaceholder(string $placeholder): PlaceholderInterface
    {
        $this->setData(self::PLACEHOLDER, $placeholder);

        return $this;
    }

    /**
     * @return int
     */
    public function getAttributeId(): int
    {
        return (int)$this->getData(self::ATTRIBUTE_ID);
    }

    /**
     * @param int $attributeId
     *
     * @return PlaceholderInterface
     */
    public function setAttributeId(int $attributeId): PlaceholderInterface
    {
        $this->setData(self::ATTRIBUTE_ID, $attributeId);

        return $this;
    }
}
