<?php
namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Model\ConfigFactory;

/**
 * Clears the assigned custom theme for a vendor when the admin explicitly
 * selects the "Default Theme" option from the admin Vendor Edit screen.
 *
 * The existing Vnecoms\VendorsCustomTheme observer only saves when a non-empty
 * value is submitted, so without this observer the admin has no way to revert
 * a vendor back to the default theme.
 */
class AdminClearVendorTheme implements ObserverInterface
{
    const CONFIG_PATH = 'custom_theme/general/theme';

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @param ConfigFactory $configFactory
     */
    public function __construct(ConfigFactory $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $vendor = $observer->getVendor();
        $request = $observer->getRequest();

        if (!$vendor || !$vendor->getId() || !$request) {
            return;
        }

        $vendorData = $request->getParam('vendor_data');
        if (!is_array($vendorData) || !array_key_exists('vendor_custom_theme', $vendorData)) {
            return;
        }

        $submittedValue = $vendorData['vendor_custom_theme'];
        if (!empty($submittedValue)) {
            return;
        }

        $config = $this->configFactory->create();
        $configCollection = $config->getCollection()
            ->addFieldToFilter('vendor_id', $vendor->getId())
            ->addFieldToFilter('path', self::CONFIG_PATH);
        foreach ($configCollection as $item) {
            $item->delete();
        }
    }
}
