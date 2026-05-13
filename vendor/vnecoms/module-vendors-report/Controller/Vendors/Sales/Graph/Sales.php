<?php

namespace Vnecoms\VendorsReport\Controller\Vendors\Sales\Graph;

use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Vnecoms\VendorsReport\Model\Source\Period;

class Sales extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsReport::report_sales_overview';
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    
    /**
     * @var \Vnecoms\VendorsReport\Model\Report\Sales
     */
    protected $_salesReport;

    
    /**
     * Constructor
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\Vendors\App\ConfigInterface $config
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\VendorsDashboard\Model\Graph $graph
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Vnecoms\VendorsReport\Model\Report\Sales $salesReport
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_salesReport = $salesReport;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $date = $this->getRequest()->getParam('date');
        $date = explode("_", $date);
        $from = $date[0];
        $to = $date[1];
        
        $vendor = $this->_session->getVendor();
        $vendorId = $vendor->getId();
        $data = [
            'report_data' => [
                'report_sales' => [
                    Period::PERIOD_DAY => $this->_salesReport->getOrderTotalsDataForGraph(
                        $from,
                        $to,
                        Period::PERIOD_DAY,
                        $vendorId
                    ),
                    Period::PERIOD_MONTH => $this->_salesReport->getOrderTotalsDataForGraph(
                        $from,
                        $to,
                        Period::PERIOD_MONTH,
                        $vendorId
                    ),
                    Period::PERIOD_QUARTER => $this->_salesReport->getOrderTotalsDataForGraph(
                        $from,
                        $to,
                        Period::PERIOD_QUARTER,
                        $vendorId
                    ),
                    Period::PERIOD_YEAR => $this->_salesReport->getOrderTotalsDataForGraph(
                        $from,
                        $to,
                        Period::PERIOD_YEAR,
                        $vendorId
                    ),
                ]
            ]
        ];
        /* $this->_session->setData('vendor_report_date_range', $this->getRequest()->getParam('type')); */
        $response->setData($data);
        return $this->_resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
