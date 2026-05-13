<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup;

use Amasty\CheckoutCore\Setup\Operation\AddAttributesToManageCheckoutFields;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RecurringData implements InstallDataInterface
{
    /**
     * @var AddAttributesToManageCheckoutFields
     */
    private $addAttributesToManageCheckoutFields;

    public function __construct(
        AddAttributesToManageCheckoutFields $addAttributesToManageCheckoutFields
    ) {
        $this->addAttributesToManageCheckoutFields = $addAttributesToManageCheckoutFields;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->addAttributesToManageCheckoutFields->execute();
    }
}
