<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

class Wysiwyg extends \Magento\Catalog\Controller\Adminhtml\Product\Wysiwyg
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::catalog_category';
}
