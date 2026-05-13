<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class NewAction extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app_action_save';
    /**
     * New app action (forward to edit action).
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
