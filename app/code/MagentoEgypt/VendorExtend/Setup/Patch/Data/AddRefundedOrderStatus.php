<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;

class AddRefundedOrderStatus implements DataPatchInterface
{
    public const STATUS_CODE = 'refunded';
    public const STATUS_LABEL = 'Refunded';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var StatusResource
     */
    private $statusResource;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        StatusResource $statusResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $status = $this->statusFactory->create();
        $this->statusResource->load($status, self::STATUS_CODE);

        if (!$status->getStatus()) {
            $status->setData([
                'status' => self::STATUS_CODE,
                'label' => self::STATUS_LABEL,
            ]);
            $this->statusResource->save($status);
        }

        foreach ([Order::STATE_PROCESSING, Order::STATE_COMPLETE, Order::STATE_CLOSED] as $state) {
            $status->assignState($state, false, true);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
