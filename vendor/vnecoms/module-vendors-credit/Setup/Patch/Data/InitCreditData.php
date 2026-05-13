<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCredit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Vnecoms\VendorsCredit\Model\ResourceModel\Withdrawal\CollectionFactory as WithdrawalCollectionFactory;

/**
 * Class add customer updated attribute to customer
 */
class InitCreditData implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WithdrawalCollectionFactory
     */
    private $withdrawalCollectionFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;

    /**
     * InitCreditData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WithdrawalCollectionFactory $withdrawalCollectionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serialize
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WithdrawalCollectionFactory $withdrawalCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serialize = $serialize;
        $this->withdrawalCollectionFactory = $withdrawalCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $withdrawalCollection = $this->withdrawalCollectionFactory->create();
        foreach($withdrawalCollection as $withdrawal){
            $additionalData = $this->serialize->unserialize($withdrawal->getAdditionalInfo());
            if(!is_array($additionalData)){
                $additionalData = $this->serialize->serialize($additionalData);
                $withdrawal->setAdditionalInfo($additionalData)->save();;
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
