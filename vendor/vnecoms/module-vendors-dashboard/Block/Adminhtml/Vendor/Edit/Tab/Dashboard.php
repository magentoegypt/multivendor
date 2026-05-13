<?php
namespace Vnecoms\VendorsDashboard\Block\Adminhtml\Vendor\Edit\Tab;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;

class Dashboard extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsDashboard::vendor/edit/tab/dashboard.phtml';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;


    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $creditFactory;

    /**
     * @var \Vnecoms\Credit\Model\Credit\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Data
     */
    protected $helper;

    /**
     * @param \Vnecoms\Credit\Model\CreditFactory $creditFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Vnecoms\Credit\Model\CreditFactory $creditFactory,
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory,
        \Vnecoms\VendorsSales\Model\OrderFactory $orderFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Block\Template\Context $context,
        Registry $coreRegistry,
        \Vnecoms\VendorsCredit\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->creditFactory    = $creditFactory;
        $this->priceCurrency    = $priceCurrency;
        $this->orderFactory     = $orderFactory;
        $this->coreRegistry     = $coreRegistry;
        $this->productFactory   = $productFactory;
        $this->transactionFactory = $transactionFactory;
        $this->date = $date;
        $this->helper = $helper;
        return $this;
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Is Enabled Escrow Transaction
     *
     * @return boolean
     */
    public function isEnabledEscrowTransaction(){
        $vendor = $this->getVendor();
        return $this->helper->isEnabledEscrowTransaction($vendor->getGroupId());
    }

    /**
     * Get Total Pending Credit.
     *
     * @return string
     */
    public function getTotalPendingCredit(){
        $vendor = $this->getVendor();
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Vnecoms\VendorsCredit\Model\ResourceModel\Escrow */
        $escrowResource = $om->create('Vnecoms\VendorsCredit\Model\ResourceModel\Escrow');
        return $escrowResource->getTotalPendingCredit($vendor);
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->coreRegistry->registry('current_vendor');
    }

    /**
     * Get get credit amount of current vendor
     * @return float
     */
    public function getCreditAmount()
    {
        if (!$this->getData('credit_amount')) {
            $creditAccount = $this->creditFactory->create();
            $creditAccount->loadByCustomerId($this->getVendor()->getCustomer()->getId());

            $this->setData('credit_amount', $creditAccount->getCredit());
        }
        return $this->getData('credit_amount');
    }

    /**
     * Get lifetime sales
     * @return float
     */
    public function getLifetimeSales()
    {
        return $this->orderFactory->create()->getResource()->getLifetimeSales($this->getVendor()->getId());
    }

    /**
     * Get Average orders
     * @return float
     */
    public function getAverageOrders()
    {
        return $this->orderFactory->create()->getResource()->getAverageOrders($this->getVendor()->getId());
    }

    /**
     * Get number of products of current vendor
     *
     * @return int
     */
    public function getTotalProducts()
    {
        $resource = $this->productFactory->create()->getResource();

        $connection = $resource->getConnection();
        $select = $connection->select();
        $select->from(
            $resource->getTable('catalog_product_entity'),
            ['total_product' => 'count( entity_id )']
        )->where(
            'vendor_id = :vendor_id'
        );
        $bind = ['vendor_id' => $this->getVendor()->getId()];

        $total = $connection->fetchOne($select, $bind);
        return $total;
    }

    /**
     * Format Price currency
     * @param float $amount
     * @return string
     */
    public function formatPrice($amount)
    {
        return $this->_storeManager->getStore()->getBaseCurrency()->formatPrecision($amount, 2, [], false);
    }

    /**
     * @return bool|\Magento\Framework\Phrase|mixed|string|string[]
     */
    public function getCreditLabelTab() {
        if (!$this->helper->isEnableCredit()) {
            return false;
        }
        return __("Credit Amount");
    }
}
