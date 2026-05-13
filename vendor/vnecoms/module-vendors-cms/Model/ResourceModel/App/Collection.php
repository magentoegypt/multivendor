<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel\App;

use Vnecoms\VendorsCms\Model\ResourceModel\AbstractCollection;

/**
 * Class Collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'app_id';

    /**
     * Fields map for corellation names & real selected fields.
     *
     * @var array
     */
    protected $_map = ['fields' => ['type' => 'type']];

    /**
     * Constructor.
     */
    protected function _construct()
    {
        //parent::_construct();
        $this->_init('Vnecoms\VendorsCms\Model\App', 'Vnecoms\VendorsCms\Model\ResourceModel\App');
        $this->_map['fields']['app_id'] = 'main_table.app_id';
    }
}
