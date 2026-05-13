<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Template\Grid\Renderer;

/**
 * Adminhtml system templates grid block type item renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Send extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Email template types
     *
     * @var array
     */
    protected static $_types = [
        '1' => 'Customer',
        '2' => 'Vendor',
    ];

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $str = __('Unknown');

        if (isset(self::$_types[$row->getTypeSendMail()])) {
            $str = self::$_types[$row->getTypeSendMail()];
        }

        return __($str);
    }
}
