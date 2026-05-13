<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSales\Plugin\Shipment;

use Magento\Framework\App\RequestInterface;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;
use Magento\Sales\Api\Data\ShipmentInterface;
use Vnecoms\VendorsSales\Model\Order;
use Vnecoms\VendorsSales\Model\Order\ShipmentFactory;
use Magento\Sales\Api\Data\ShipmentExtensionFactory;


class AssignSourceCodeToShipmentPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ShipmentExtensionFactory
     */
    private $shipmentExtensionFactory;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $moduleManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectmanager;

    /**
     * @param RequestInterface $request
     * @param ShipmentExtensionFactory $shipmentExtensionFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        RequestInterface $request,
        ShipmentExtensionFactory $shipmentExtensionFactory,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->request = $request;
        $this->shipmentExtensionFactory = $shipmentExtensionFactory;
        $this->moduleManager = $moduleManager;
        $this->objectmanager = $objectmanager;
    }

    /**
     * @param ShipmentFactory $subject
     * @param ShipmentInterface $shipment
     * @param Order $order
     * @return ShipmentInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreateVendorShipment(ShipmentFactory $subject, ShipmentInterface $shipment, Order $order)
    {

        if ($this->moduleManager->isEnabled('Magento_InventoryCatalogApi')) {
            $sourceCode = $this->request->getParam('sourceCode');
            if (empty($sourceCode)) {
                $websiteId = $order->getOrder()->getStore()->getWebsiteId();
                $stockByWebsiteIdResolver = $this->objectmanager->create(
                    \Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface::class);

                $getSourcesAssignedToStockOrderedByPriority = $this->objectmanager->create(
                    \Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface::class);

                $defaultSourceProvider = $this->objectmanager->create(
                    \Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface::class);

                $stockId = $stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();
                $sources = $getSourcesAssignedToStockOrderedByPriority->execute((int)$stockId);
                //TODO: need ro rebuild this logic | create separate service
                if (!empty($sources) && count($sources) == 1) {
                    $sourceCode = $sources[0]->getSourceCode();
                } else {
                    $sourceCode = $defaultSourceProvider->getCode();
                }
            }
        }

        $shipmentExtension = $shipment->getExtensionAttributes();

        if (empty($shipmentExtension)) {
            $shipmentExtension = $this->shipmentExtensionFactory->create();
        }
        $shipmentExtension->setSourceCode($sourceCode);
        $shipment->setExtensionAttributes($shipmentExtension);

        return $shipment;
    }
}
