<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template;

/**
 * Block Extender For Expand Sections
 */
class Expander extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_CheckoutCore::system/config/form/expander.phtml';

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->getRequest()->getParam('section');
    }

    /**
     * @return string
     */
    public function getExpand()
    {
        return $this->getRequest()->getParam('expand');
    }
}
