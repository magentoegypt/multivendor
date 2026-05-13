<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Vnecoms\VendorsCategory\Block\Vendors\Category\AbstractCategory;

/**
 * Class SaveButton.
 */
class SaveButton extends AbstractCategory implements ButtonProviderInterface
{
    /**
     * Save button.
     *
     * @return array
     */
    public function getButtonData()
    {
        // $category = $this->getCategory();

     //   if($category->getId())
     //   {
            return [
                'label' => __('Save'),
                'class' => 'save primary',
                'onclick' => "categorySubmit('".$this->getSaveUrl()."', true)",
                'sort_order' => 30,
            ];
      //  }

     //   return [];
    }
}
