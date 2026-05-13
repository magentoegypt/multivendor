<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Layout Builder for Magento 2 (System)
 */

namespace Amasty\CheckoutLayoutBuilder\Model\Config\Backend;

use Amasty\CheckoutLayoutBuilder\Model\ConfigProvider;
use Magento\Framework\App\Config\Data\ProcessorInterface;
use Magento\Framework\App\Config\Value;

/**
 * For deleting value in hidden field, when we use "inherit".
 * Layout config have 2 fields and only 1 inherit checkbox.
 * @since 3.0.0
 */
class LayoutBuilderSave extends Value implements ProcessorInterface
{
    /**
     * Process config value
     *
     * @param string $value Raw value of the configuration field
     * @return string Processed value
     */
    public function processValue($value): string
    {
        return $value;
    }

    /**
     * Don't allow save is inherit box checked.
     *
     * @return bool
     */
    public function isSaveAllowed(): bool
    {
        return parent::isSaveAllowed() && !$this->isInherit();
    }

    /**
     * Processing object after delete data
     *
     * @return $this
     */
    public function afterDelete(): self
    {
        if ($this->isInherit()) {
            $pathListToDelete = [
                ConfigProvider::PATH_PREFIX . ConfigProvider::LAYOUT_BUILDER_BLOCK
                . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                ConfigProvider::PATH_PREFIX . ConfigProvider::DESIGN_BLOCK
                . ConfigProvider::FIELD_CHECKOUT_LAYOUT_MODERN,
                ConfigProvider::PATH_PREFIX . ConfigProvider::DESIGN_BLOCK . ConfigProvider::FIELD_CHECKOUT_LAYOUT,
                ConfigProvider::PATH_PREFIX . ConfigProvider::DESIGN_BLOCK . ConfigProvider::FIELD_CHECKOUT_DESIGN,
            ];
            $this->_resourceCollection
                ->addFieldToFilter('path', $pathListToDelete)
                ->addFieldToFilter('scope_id', $this->getScopeId())
                ->addFieldToFilter('scope', $this->getScope())
                ->walk('delete');
        }

        return parent::afterDelete();
    }

    /**
     * Is "use parent config" checkbox checked.
     *
     * @return bool
     */
    private function isInherit(): bool
    {
        $data = $this->getData('groups');

        return (bool)(int)$this->walkGet(
            [
                'design',
                'groups',
                'layout',
                'fields',
                ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                'inherit'
            ],
            $data
        );
    }

    /**
     * @param array $path
     * @param array $haystack
     * @return mixed|null
     */
    private function walkGet(array $path, array $haystack)
    {
        $key = array_shift($path);

        $value = $haystack[$key] ?? null;

        if (count($path) && is_array($value)) {
            return $this->walkGet($path, $value);
        }

        return $value;
    }
}
