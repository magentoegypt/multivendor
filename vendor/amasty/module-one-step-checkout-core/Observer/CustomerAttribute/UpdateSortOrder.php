<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\CustomerAttribute;

use Amasty\CheckoutCore\Model\CustomerAttribute\UpdateSortOrder as SortOrderModel;
use Magento\Customer\Model\Attribute;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class UpdateSortOrder implements ObserverInterface
{
    /**
     * @var SortOrderModel
     */
    private $sortOrderModel;

    public function __construct(SortOrderModel $sortOrderModel)
    {
        $this->sortOrderModel = $sortOrderModel;
    }

    /**
     * Event: amasty_customer_attributes_before_save
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var Attribute $attribute */
        $attribute = $observer->getEvent()->getData('object');

        $this->sortOrderModel->execute($attribute);
    }
}
