<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

class NewAction extends \Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}