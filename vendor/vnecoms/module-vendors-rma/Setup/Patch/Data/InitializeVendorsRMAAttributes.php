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
use Magento\Framework\App\ResourceConnection;

class InitializeVendorsRMAAttributes implements DataPatchInterface, PatchVersionInterface
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
     * @var ResourceConnection
     */
    protected $resource;

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
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Vnecoms\RMA\Model\StatusFactory $statusFactory,
        \Vnecoms\RMA\Setup\RmaSetupFactory $rmaSetupFactory,
        StateFactory $stateFactory,
        TemplateFactory $templateFactory,
        EscalateFactory $escalateFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->rmaSetupFactory = $rmaSetupFactory;
        $this->stateFactory = $stateFactory;
        $this->templateFactory = $templateFactory;
        $this->escalateFactory = $escalateFactory;
        $this->_storeManager = $storeManager;
        $this->resource = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $rmaSetup = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $rmaSetup->addAttribute(
            Request::ENTITY,
            'vendor_id',
            [
                'label' => 'Vendor Id',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'required' => false,
                'sort_order' => 20,
            ]
        );

        $rmaSetup->addAttribute(
            Request::ENTITY,
            'refund_amount',
            [
                'label' => 'Refund Amount',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'required' => false,
                'sort_order' => 20,
            ]
        );

        $rmaSetup->addAttribute(
            \Vnecoms\RMA\Model\Request::ENTITY,
            'is_vendor_read',
            [
                'label' => 'Is Vendor Read',
                'type' => 'static',
                'input' => 'text',
                'position' => 145,
                'required' => false,
                'sort_order' => 20,
            ]
        );

        $patterns = [
            [
                'status_id' => 8,
                'title' => 'Being Reviewed By Admin',
                'code' => \Vnecoms\VendorsRMA\Model\Request::STATUS_BEING,
                'is_main' => 1,
                'status' => \Vnecoms\RMA\Model\Source\Status::STATUS_ENABLED
            ],
            [
                'status_id' => 9,
                'title' => 'Escalated',
                'code' => \Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING,
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

            if ($data['code'] == \Vnecoms\VendorsRMA\Model\Request::STATUS_BEING) {
                $state = \Vnecoms\VendorsRMA\Model\Request::STATE_BEING;
            } elseif ($data['code'] == \Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING) {
                $state = \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING;
            }

            $this->createState()->setData( [
                'state' => $state,
                'status_id' => $data['status_id']
            ])->save();
        }


        $patterns = array();
        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $patterns[] = [
                        [
                            'template_customer_notify' => 'rma_request_email_status_being_customer',
                            'template_admin_notify' => 'rma_request_email_status_being_admin',
                            'template_status_id' => 8,
                            'store_id'=>$store->getId()
                        ],
                        [
                            'template_customer_notify' => 'rma_request_email_status_awaiting_customer',
                            'template_admin_notify' => 'rma_request_email_status_awaiting_admin',
                            'template_status_id' => 9,
                            'store_id'=>$store->getId()
                        ],
                    ];
                }
            }
        }


        /**
         * Insert default and system state
         */
        foreach ($patterns as $datas) {
            foreach($datas as $data){
                $this->createStatusTemplate()->setData($data)->save();
            }

        }


        $patterns = [
            [
                'template_code' => 'Template For Customer',
                'template_text' => '{{template config_path="design/email/header_template"}}
                        <h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;">
                            {{trans "Hello %name" name=$request.getCustomerName()}},
                        </h1>

                        <p> {{trans "RMA #%increment_id has been processed by admin" increment_id=$request.getIncrementId()}},</p>

                        <p>
                            {{trans \'If you have questions about your order, you can email us at <a href="mailto:%store_email">%store_email</a>\' store_email=$store_email |raw}}{{depend store_phone}} {{trans \'or call us at <a href="tel:%store_phone">%store_phone</a>\' store_phone=$store_phone |raw}}{{/depend}}.
                            {{depend store_hours}}
                            {{trans \'Our hours are <span class="no-link">%store_hours</span>.\' store_hours=$store_hours |raw}}
                            {{/depend}}
                        </p>
                        <p>{{var custom_message}}</p>
                        <p>{{trans "Items"}}<br />
                            {{layout handle="rma_email_request_items" rma=$request}}</p>

                        {{template config_path="design/email/footer_template"}}',
                'template_type' => 2,
                'type_send_mail' => 1 ,
                'use_custom_message' => 1 ,
                'template_subject' => 'RMA # {{var request.getIncrementId()}}'
            ],
            [
                'template_code' => 'Template For Vendor',
                'template_text' => '{{template config_path="design/email/header_template"}}
                        <h1 style="font-size:22px; font-weight:normal; line-height:22px; margin:0 0 11px 0;">
                            {{trans "Hello %name" name=$request.getContactsName()}},
                        </h1>

                        <p> {{trans "RMA #%increment_id has been processed by admin" increment_id=$request.getIncrementId()}},</p>

                        <p>
                            {{trans \'If you have questions about your order, you can email us at <a href="mailto:%store_email">%store_email</a>\' store_email=$store_email |raw}}{{depend store_phone}} {{trans \'or call us at <a href="tel:%store_phone">%store_phone</a>\' store_phone=$store_phone |raw}}{{/depend}}.
                            {{depend store_hours}}
                            {{trans \'Our hours are <span class="no-link">%store_hours</span>.\' store_hours=$store_hours |raw}}
                            {{/depend}}
                        </p>
                        <p>{{var custom_message}}</p>
                        <p>{{trans "Items"}}<br />
                            {{layout handle="rma_email_request_items" rma=$request}}</p>

                        {{template config_path="design/email/footer_template"}}',
                'template_type' => 2,
                'type_send_mail' => 2 ,
                'use_custom_message' => 1 ,
                'template_subject' => 'RMA # {{var request.getIncrementId()}}'
            ]
        ];

        foreach ($patterns as $data) {
            $this->createEscalateTemplate()->setData($data)->save();
        }
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
