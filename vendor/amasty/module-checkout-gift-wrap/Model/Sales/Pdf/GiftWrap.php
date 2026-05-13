<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Gift Wrap for Magento 2 (System)
 */

namespace Amasty\CheckoutGiftWrap\Model\Sales\Pdf;

use Amasty\CheckoutCore\Api\FeeRepositoryInterface;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

class GiftWrap extends DefaultTotal
{
    /**
     * @var FeeRepositoryInterface
     */
    protected $feeRepository;

    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        FeeRepositoryInterface $feeRepository,
        array $data = []
    ) {

        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
        $this->feeRepository = $feeRepository;
    }

    /**
     * @return float|int|null
     */
    public function getAmount()
    {
        $fee = $this->feeRepository->getByOrderId($this->getSource()->getOrderId());

        if (!$fee->getData()) {
            return null;
        }

        return $fee->getAmount();
    }

    /**
     * @return bool
     */
    public function canDisplay()
    {
        $amount = $this->getAmount();

        return $this->getDisplayZero() === 'true' && $amount !== null;
    }
}
