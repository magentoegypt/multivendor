<?php

namespace Vnecoms\RMA\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Vnecoms\RMA\Model\StatusFactory;
use Vnecoms\RMA\Model\Request\Status\StateFactory;
use Vnecoms\RMA\Model\Request\Status\TemplateFactory;
use Vnecoms\RMA\Model\ReasonFactory;
use Vnecoms\RMA\Setup\RmaSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\App\ResourceConnection;

class InitializeRMAAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $rmaSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var StateFactory
     */
    private $stateFactory;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @var ReasonFactory
     */
    private $reasonFactory;

    /**
     * store manager
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * InitializeRMAAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RmaSetupFactory $rmaSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param StatusFactory $statusFactory
     * @param StateFactory $stateFactory
     * @param TemplateFactory $templateFactory
     * @param ReasonFactory $reasonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RmaSetupFactory $rmaSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        StatusFactory $statusFactory,
        StateFactory $stateFactory,
        TemplateFactory $templateFactory,
        ReasonFactory $reasonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ResourceConnection $resource
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->rmaSetupFactory = $rmaSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->statusFactory = $statusFactory;
        $this->stateFactory = $stateFactory;
        $this->templateFactory = $templateFactory;
        $this->reasonFactory = $reasonFactory;
        $this->_storeManager = $storeManager;
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var CategorySetup $categorySetup */
        $rmaSetup = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $rmaSetup->installEntities();

        $patterns = [
            [
                'status_id' => 1,
                'title' => 'Open',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_PENDING,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'status_id' => 2,
                'title' => 'Request Accepted',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_APPROVAL,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'status_id' => 3,
                'title' => 'Package Sent',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_PACKSENT,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'status_id' => 4,
                'title' => 'Package Received',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_RECEIVED,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],

            [
                'status_id' => 5,
                'title' => 'Package Is Returned',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_RETURNED,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],

            [
                'status_id' => 6,
                'title' => 'Canceled',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_CANCELED,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'status_id' => 7,
                'title' => 'Resolved',
                'code' => \Vnecoms\RMA\Model\Request::STATUS_RESOLVED,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
        ];

        $connection = $this->resource->getConnection();
        /**
         * Insert default and system status
         */
        foreach ($patterns as $data) {
            $connection->insert($this->resource->getTableName('ves_rma_status'), $data);

            if (in_array($data['code'], [
                \Vnecoms\RMA\Model\Request::STATUS_PENDING,
                \Vnecoms\RMA\Model\Request::STATUS_APPROVAL,
                \Vnecoms\RMA\Model\Request::STATUS_PACKSENT,
                \Vnecoms\RMA\Model\Request::STATUS_RECEIVED,
                \Vnecoms\RMA\Model\Request::STATUS_RETURNED
            ])) {
                $state = \Vnecoms\RMA\Model\Request::STATE_OPEN;
            } elseif ($data['code'] == \Vnecoms\RMA\Model\Request::STATUS_CANCELED) {
                $state = \Vnecoms\RMA\Model\Request::STATE_CANCELED;
            } elseif ($data['code'] == \Vnecoms\RMA\Model\Request::STATUS_RESOLVED) {
                $state = \Vnecoms\RMA\Model\Request::STATE_CLOSED;
            }

            $this->createState()->setData( [
                'state' => $state,
                'status_id' => $data['status_id']
            ])->save();
        }

        $patterns = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $patterns[] = [
                        [
                            'template_customer_notify' => 'rma_request_email_status_pending_customer',
                            'template_admin_notify' => 'rma_request_email_status_pending_admin',
                            'template_status_id' => 1,
                            'store_id'=>$store->getId()
                        ],
                        [
                            'template_customer_notify' => 'rma_request_email_status_approval_customer',
                            'template_admin_notify' => 'rma_request_email_status_approval_admin',
                            'template_status_id' => 2,
                            'store_id'=>$store->getId()
                        ],
                        [
                            'template_customer_notify' => 'rma_request_email_status_package_sent_customer',
                            'template_admin_notify' => 'rma_request_email_status_package_sent_admin',
                            'template_status_id' => 3,
                            'store_id'=>$store->getId()
                        ],
                        [
                            'template_customer_notify' => 'rma_request_email_status_package_received_customer',
                            'template_admin_notify' => 'rma_request_email_status_package_received_admin',
                            'template_status_id' => 4,
                            'store_id'=>$store->getId()
                        ],

                        [
                            'template_customer_notify' => 'rma_request_email_status_package_returned_customer',
                            'template_admin_notify' => 'rma_request_email_status_package_returned_admin',
                            'template_status_id' => 5,
                            'store_id'=>$store->getId()
                        ],

                        [
                            'template_customer_notify' => 'rma_request_email_status_canceled_customer',
                            'template_admin_notify' => 'rma_request_email_status_canceled_admin',
                            'template_status_id' => 6,
                            'store_id'=>$store->getId()
                        ],
                        [
                            'template_customer_notify' => 'rma_request_email_status_resolved_customer',
                            'template_admin_notify' => 'rma_request_email_status_resolved_admin',
                            'template_status_id' => 7,
                            'store_id'=>$store->getId()
                        ]
                    ];
                }
            }
        }


        /**
         * Insert default and system state
         */
        foreach ($patterns as $datas) {
            foreach ($datas as $data) {
                $this->createStatusTemplate()->setData($data)->save();
            }
        }


        $patterns = [
            [
                'title' => 'Metal Detectors',
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'title' => 'It\'s the wrong type',
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],

            [
                'title' => 'It\'s broken',
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],

            [
                'title' => 'Faulty',
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'title' => 'I\'ve changed my mind',
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
        ];
        /**
         * Insert default and system Reason
         */
        foreach ($patterns as $data) {
            $this->createReason()->setData($data)->save();
        }
        $rmaSetup->addAttribute(
            \Vnecoms\RMA\Model\Request::ENTITY,
            'is_admin_read',
            [
                'label' => 'Is Admin Read',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'required' => false,
                'sort_order' => 20,
            ]
        );

        $rmaSetup->addAttribute(
            \Vnecoms\RMA\Model\Request::ENTITY,
            'is_customer_read',
            [
                'label' => 'Is Customer Read',
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
        return [];
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

    /**
     * Create Reason
     *
     * @return Reason
     */
    public function createReason()
    {
        return $this->reasonFactory->create();
    }
}
