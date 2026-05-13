<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Framework\Api;

use Amasty\CheckoutCore\Plugin\Quote\Model\Cart\CartTotalRepository as CartTotalRepositoryPlugin;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\TotalsInterface;

class DataObjectHelperPlugin
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * resolve fatal
     * @see \Amasty\CheckoutCore\Plugin\Quote\Model\Cart\CartTotalRepository::beforeGet
     *
     * @param \Magento\Framework\Api\DataObjectHelper $subject
     * @param object                                  $dataObject
     * @param array                                   $data
     * @param string                                  $interfaceName
     *
     * @return array
     */
    public function beforePopulateWithArray(
        \Magento\Framework\Api\DataObjectHelper $subject,
        $dataObject,
        array $data,
        $interfaceName
    ) {
        if ($interfaceName === TotalsInterface::class
            && $this->registry->registry(CartTotalRepositoryPlugin::REGISTRY_IGNORE_EXTENSION_ATTRIBUTES_KEY)
        ) {
            unset($data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);
        }

        return [$dataObject, $data, $interfaceName];
    }
}
