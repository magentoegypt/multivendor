<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Customer attribute model
 *
 * @method int getSortOrder()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attribute extends \Magento\Eav\Model\Entity\Attribute
{
    /**
     * Name of the module
     */
    const MODULE_NAME = 'Vnecoms_RMA';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'request_entity_attribute';

    /**
     * Prefix of model events object
     *
     * @var string
     */
    protected $_eventObject = 'attribute';

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Attribute');
    }


    /**
     * This fixes https://github.com/magento/magento2/issues/5339
     *
     * @inheritdoc
     */
    public function __sleep()
    {
        $this->unsetData('entity_type');
        return parent::__sleep();
    }
}
