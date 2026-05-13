<?php

namespace Vnecoms\VendorsSales\Plugin\Address;

class RendererPlugin
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager  
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Set store id to address before formatting.
     *
     * @param \Magento\Sales\Model\Order\Address\Renderer $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Address $address
     * @param string $type
     * @return string|null
     */
    public function aroundFormat(
        \Magento\Sales\Model\Order\Address\Renderer $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order\Address $address,
        $type
    ) {
        $currentStore= $this->storeManager->getStore();
        $result = $proceed($address, $type);
        $this->storeManager->setCurrentStore($currentStore);
        return $result;
    }
}
