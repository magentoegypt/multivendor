<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Sms\Block\Customer\Account;

use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * Shopping cart item render block for configurable products.
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
{
    /**
     * Disable escape html for this block.
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

}
