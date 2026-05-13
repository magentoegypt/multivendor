<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Rule;

/**
 * Class Rule.
 */
class Rule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var Condition\CombineFactory
     */
    protected $conditionsFactory;

    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Data\FormFactory                          $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
     * @param Condition\CombineFactory                                     $conditionsFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Vnecoms\VendorsCms\Model\Rule\Condition\CombineFactory $conditionsFactory,
        \Vnecoms\VendorsCms\Model\Rule\Action\Collection $vendorActions,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->conditionsFactory = $conditionsFactory;
        $this->_vendorActions = $vendorActions;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_actions = $vendorActions;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionsInstance()
    {
        return $this->conditionsFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsInstance()
    {
        return;
    }
}
