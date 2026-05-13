<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors;

use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;

/**
 * Catalog product controller
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class Vendors extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma';
    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\Vendors\App\ConfigInterface $config
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context
    ) {
        parent::__construct($context);
    }
    
}
