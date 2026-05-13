<?php
/**
 * Copyright � 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Block\Vendors\Tablerate\Edit;

class Tabs extends \Vnecoms\Vendors\Block\Vendors\Widget\Tabs
{
    protected $_coreRegistry;
    /**
     * @return void
     */
    protected function _construct()
    {

        parent::_construct();
        $this->setId('table_rate_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Rate Information'));
    }

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = [])
    {
        $this->_coreRegistry = $registry;
        return parent::__construct($context, $jsonEncoder, $authSession);
    }
}
