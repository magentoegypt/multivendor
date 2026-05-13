<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

class NewAction extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}