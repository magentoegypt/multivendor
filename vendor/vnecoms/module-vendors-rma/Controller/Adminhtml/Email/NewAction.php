<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Email;

class NewAction extends Template
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}