<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSales\Plugin\Invoice;

use Vnecoms\VendorsSales\Model\Order;
use Vnecoms\VendorsSales\Model\Order\InvoiceFactory;

class View
{
    /**
     * @var InvoiceFactory
     */
    private $vendorInvoiceFactory;

    /**
     * Tax module helper
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManage;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param InvoiceFactory $vendorInvoiceFactory
     * @param \Magento\Framework\Module\Manager $moduleManage
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        InvoiceFactory $vendorInvoiceFactory,
        \Magento\Framework\Module\Manager $moduleManage,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->vendorInvoiceFactory = $vendorInvoiceFactory;
        $this->_moduleManage    = $moduleManage;
        $this->request = $request;
    }

    public function aroundGetCreditMemoUrl(
        \Magento\Sales\Block\Adminhtml\Order\Invoice\View $subject,
        callable $proceed
    )
    {
        $vendorId = $this->request->getParam('vendor_id') ?
            $this->request->getParam('vendor_id') : 0;
        ;
        $invoice = $subject->getInvoice();
        $vendorInvoice = $this->vendorInvoiceFactory->create()->getCollection()
            ->addFieldToFilter("invoice_id", $invoice->getId())
            ->addFieldToFilter("vendor_id", $vendorId)
            ->getFirstItem();

        if ($vendorInvoice->getId()) {
            $isVendorShip  = $this->_moduleManage->isEnabled("Vnecoms_VendorsShipping");
            $om  = \Magento\Framework\App\ObjectManager::getInstance();
            $helperData = $om->create(\Vnecoms\VendorsShipping\Helper\Data::class);
            if ($isVendorShip) {
                if (!$helperData->isEnabled()) {
                    return $subject->getUrl(
                        'sales/order_creditmemo/start',
                        ['order_id' => $invoice->getOrder()->getId(), 'invoice_id' => $invoice->getId()]
                    );
                }
            }
            return $subject->getUrl(
                'vendors/sales_creditmemo/start',
                ['vorder_id' => $vendorInvoice->getVendorOrder()->getId(), 'vinvoice_id' => $vendorInvoice->getId()]
            );
        }
        return $subject->getUrl(
            'sales/order_creditmemo/start',
            ['order_id' => $invoice->getOrder()->getId(), 'invoice_id' => $invoice->getId()]
        );
    }
}
