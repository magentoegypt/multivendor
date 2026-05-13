<?php

namespace Vnecoms\VendorsRMA\Setup\Patch\Data;

use Vnecoms\RMA\Model\Request;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Vnecoms\RMA\Model\Request\Status\StateFactory;
use Vnecoms\RMA\Model\Request\Status\TemplateFactory;
use Vnecoms\VendorsRMA\Model\Request\Escalate\TemplateFactory as EscalateFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class InitVendorOrderId implements DataPatchInterface, PatchVersionInterface
{
    /**
     *
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    private $statusFactory;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $rmaSetupFactory;

    /**
     *
     * @var \Vnecoms\RMA\Model\Request\Status\StateFactory
     */
    private $stateFactory;

    /**
     *
     * @var \Vnecoms\RMA\Model\Request\Status\TemplateFactory
     */
    private $templateFactory;

    /**
     *
     * @var \Vnecoms\VendorsRMA\Model\Request\Escalate\TemplateFactory
     */
    private $escalateFactory;

    /**
     * store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * InitializeVendorsRMAAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Vnecoms\RMA\Model\StatusFactory $statusFactory
     * @param \Vnecoms\RMA\Setup\RmaSetupFactory $rmaSetupFactory
     * @param StateFactory $stateFactory
     * @param TemplateFactory $templateFactory
     * @param EscalateFactory $escalateFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Vnecoms\RMA\Model\StatusFactory $statusFactory,
        \Vnecoms\RMA\Setup\RmaSetupFactory $rmaSetupFactory,
        StateFactory $stateFactory,
        TemplateFactory $templateFactory,
        EscalateFactory $escalateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->rmaSetupFactory = $rmaSetupFactory;
        $this->stateFactory = $stateFactory;
        $this->templateFactory = $templateFactory;
        $this->escalateFactory = $escalateFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $rmaSetup = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $rmaSetup->addAttribute(
            Request::ENTITY,
            'vendor_order_id',
            [
                'label' => 'Vendor Order Id',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'required' => false,
                'sort_order' => 20,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [\Vnecoms\RMA\Setup\Patch\Data\InitializeRMAAttributes::class];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Create Status
     *
     * @return Status
     */
    public function createEscalateTemplate()
    {
        return $this->escalateFactory->create();
    }


    /**
     * Create Status
     *
     * @return Status
     */
    public function createStatus()
    {
        return $this->statusFactory->create();
    }

    /**
     * Create State
     *
     * @return State
     */
    public function createState()
    {
        return $this->stateFactory->create();
    }
    /**
     * Create State
     *
     * @return State
     */
    public function createStatusTemplate()
    {
        return $this->templateFactory->create();
    }
}
