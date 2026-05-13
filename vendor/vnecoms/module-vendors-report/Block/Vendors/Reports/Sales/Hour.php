<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Block\Vendors\Reports\Sales;

use Vnecoms\VendorsReport\Model\Source\Period;
use Vnecoms\VendorsReport\Block\Vendors\Reports\LayoutProcessorInterface;

class Hour implements LayoutProcessorInterface
{
    /**
     * @var \Vnecoms\VendorsReport\Model\Report\Sales
     */
    protected $_salesReport;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;
    
    /**
     * Backend URL instance
     *
     * @var \Vnecoms\Vendors\Model\UrlInterface
     */
    protected $_url;
    
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;
    
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     *
     * @param \Vnecoms\VendorsReport\Model\Report\Sales $salesReport
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     */
    public function __construct(
        \Vnecoms\VendorsReport\Model\Report\Sales $salesReport,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Vendors\Model\Session $vendorSession
    ) {
        $this->_salesReport = $salesReport;
        $this->_vendorSession = $vendorSession;
        $this->_localeFormat = $localeFormat;
        $this->_storeManager = $storeManager;
        $this->_url = $url;
    }
    
    public function process($jsLayout)
    {
        $dateRange = $jsLayout['components']['reports']['date_range'];
        $range = explode("_", $dateRange['value']);
        $from = $range[0];
        $to = strtotime($range[1]);
        
        $vendorId = $this->_vendorSession->getVendor()->getId();
        
        $jsLayout['components']['reports']['graphs_data'][$dateRange['value']]['report_sales'] = [
            Period::PERIOD_DAY => $this->_salesReport->getOrderTotalsByHour(
                $from,
                $to,
                $vendorId
            ),
        ];
        
        $jsLayout['components']['reports']['report_url'] = $this->_url->getUrl('reports/sales_graph/hour');
        $jsLayout['components']['reports']['priceFormat'] = $this->_localeFormat->getPriceFormat(null, $this->_storeManager->getStore()->getBaseCurrencyCode());
        return $jsLayout;
    }
}
