<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg;

/**
 * Images manage controller for VendorsCms WYSIWYG editor.
 *
 * @author      VES Team <core@magentocommerce.com>
 */
abstract class Blocks extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::wysiwyg_blocks';

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
}
