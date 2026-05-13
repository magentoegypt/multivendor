<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Vnecoms\VendorsCategory\Block\Vendors\Category\AbstractCategory;

/**
 * Class DeleteButton.
 */
class DeleteButton extends AbstractCategory implements ButtonProviderInterface
{
    /**
     * Delete button.
     *
     * @return array
     */
    public function getButtonData()
    {
        $category = $this->getCategory();
        $categoryId = (int) $category->getId();

        if ($categoryId && $categoryId != $this->getRootId()) {
            return [
                'id' => 'delete',
                'label' => __('Delete'),
                'on_click' => "categoryDelete('".$this->getDeleteUrl()."')",
                'class' => 'delete',
                'sort_order' => 10,
            ];
        }

        return [];
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function getDeleteUrl(array $args = [])
    {
        $params = array_merge($this->getDefaultUrlParams(), $args);

        return $this->getUrl('catalog/*/delete', $params);
    }

    /**
     * @return array
     */
    protected function getDefaultUrlParams()
    {
        return ['_current' => true, '_query' => ['isAjax' => null]];
    }
}
