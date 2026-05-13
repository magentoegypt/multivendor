<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Block\Vendors;

/**
 * Adminhtml footer block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Reports extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    const DEFAULT_DATE_RANGE = 'last-7days';
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var array|\Vnecoms\VendorsReport\Block\Vendors\Reports\LayoutProcessorInterface[]
     */
    protected $_layoutProcessors;

    /**
     * @var string
     */
    protected $_defaultDateRangeType;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Model\UrlInterface $url
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $url);
        $this->_dateTime = $dateTime;
        $this->_coreRegistry = $coreRegistry;
        $this->_vendorSession = $vendorSession;
        $this->_layoutProcessors = $layoutProcessors;

        if (isset($data['layoutProcessors']) && is_array($data['layoutProcessors'])) {
            $om = \Magento\Framework\app\ObjectManager::getInstance();
            foreach ($data['layoutProcessors'] as $key => $processorClass) {
                $this->_layoutProcessors[$key] = $om->create($processorClass);
            }
        }

        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        /*Set default date range type from layout*/
        $this->_defaultDateRangeType = isset($data['default_date_range_type'])?$data['default_date_range_type']:null;
    }

    /**
     * Get Vendor Object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_vendorSession->getVendor();
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
     * Get graph url
     * @return string
     */
    public function getGraphUrl()
    {
        return $this->getUrl('dashboard/graph');
    }

    public function getDateRanges()
    {
        $today = $this->_dateTime->date('Y-m-d');
        $todayTimeStamp = $this->_dateTime->timestamp($today);
        $endToday = $this->_dateTime->date('Y-m-d H:i:s', strtotime($today.'+23 hours +59minutes + 59seconds'));

        $dateToday = __(
            'Today: %1',
            $this->formatDate($today, \IntlDateFormatter::MEDIUM)
        );

        $yesterday = strtotime('-1 day', $todayTimeStamp);
        $yesterday = $this->_dateTime->date('Y-m-d', $yesterday);
        $endYesterday = $this->_dateTime->date('Y-m-d H:i:s', strtotime($yesterday.'+23 hours +59minutes + 59seconds'));
        $yesterdayTitle = __(
            'Yesterday: %1',
            $this->formatDate($yesterday, \IntlDateFormatter::MEDIUM)
        );

        $isSunToday = date('w', $todayTimeStamp) == 0;
        $isMonToday = date('w', $todayTimeStamp) == 1;

        if ($isSunToday) {
            $lastSundayTimeStamp = $todayTimeStamp;
            $thisWeekSun = $today;
            $thisWeekSunTodayTitle = __(
                'This week (Sun - Today): %1',
                $this->formatDate($thisWeekSun, \IntlDateFormatter::MEDIUM)
            );
        } else {
            $lastSundayTimeStamp = strtotime('previous sunday', $todayTimeStamp);
            $thisWeekSun = date('Y-m-d', $lastSundayTimeStamp);
            $thisWeekSunTodayTitle = __(
                'This week (Sun - Today): %1 - %2',
                $this->formatDate($thisWeekSun, \IntlDateFormatter::MEDIUM),
                $this->formatDate($today, \IntlDateFormatter::MEDIUM)
            );
        }

        if ($isMonToday) {
            $thisWeekMonTimeStamp = $todayTimeStamp;
            $thisWeekMon = $today;
            $thisWeekMonTodayTitle = __(
                'This week (Mon - Today): %1',
                $this->formatDate($today, \IntlDateFormatter::MEDIUM)
            );
        } else {
            $thisWeekMonTimeStamp = strtotime('previous monday', $todayTimeStamp);
            $thisWeekMon = date('Y-m-d', $thisWeekMonTimeStamp);
            $thisWeekMonTodayTitle = __(
                'This week (Mon - Today): %1 - %2',
                $this->formatDate($thisWeekMon, \IntlDateFormatter::MEDIUM),
                $this->formatDate($today, \IntlDateFormatter::MEDIUM)
            );
        }


        $last7Days = strtotime('-7 days', $todayTimeStamp);
        $last7Days = $this->_dateTime->date('Y-m-d', $last7Days);
        $last7DaysTitle = __(
            'Last 7 days: %1 - %2',
            $this->formatDate($today, \IntlDateFormatter::MEDIUM),
            $this->formatDate($last7Days, \IntlDateFormatter::MEDIUM)
        );

        $lastWeekSat = date('Y-m-d', strtotime('-1 days', $lastSundayTimeStamp));
        $lastWeekSun = date('Y-m-d', strtotime('-7 days', $lastSundayTimeStamp));
        $lastWeekSunSatTitle = __(
            'Last week (Sun - Sat): %1 - %2',
            $this->formatDate($lastWeekSun, \IntlDateFormatter::MEDIUM),
            $this->formatDate($lastWeekSat, \IntlDateFormatter::MEDIUM)
        );

        $lastWeekMon = date('Y-m-d', strtotime('-7 days', $thisWeekMonTimeStamp));
        $lastWeekSun = $thisWeekSun;

        $lastWeekMonSunTitle = __(
            'Last week (Mon - Sun): %1 - %2',
            $this->formatDate($lastWeekMon, \IntlDateFormatter::MEDIUM),
            $this->formatDate($lastWeekSun, \IntlDateFormatter::MEDIUM)
        );

        $firstDayThisMonth = date('Y-m-01', $todayTimeStamp);
        $thisMonthTitle = __(
            'This month: %1 - %2',
            $this->formatDate($firstDayThisMonth, \IntlDateFormatter::MEDIUM),
            $this->formatDate($today, \IntlDateFormatter::MEDIUM)
        );

        $last30DaysTimeStamp = strtotime('-30 days', $todayTimeStamp);
        $last30Days = $this->_dateTime->date('Y-m-d', $last30DaysTimeStamp);
        $last30DaysTitle = __(
            'Last 30 days: %1 - %2',
            $this->formatDate($last30Days, \IntlDateFormatter::MEDIUM),
            $this->formatDate($today, \IntlDateFormatter::MEDIUM)
        );

        $firstDayLastMonthTimeStamp = strtotime('-1 month', strtotime($firstDayThisMonth));
        $firstDayLastMonth = date('Y-m-d', $firstDayLastMonthTimeStamp);
        $lastDayLastMonth = date('y-m-t', $firstDayLastMonthTimeStamp);
        $lastMonthTitle = __(
            'Last month: %1 - %2',
            $this->formatDate($firstDayLastMonth, \IntlDateFormatter::MEDIUM),
            $this->formatDate($lastDayLastMonth, \IntlDateFormatter::MEDIUM)
        );

        $vendorCreatedDate = $this->_dateTime->date('Y-m-d', $this->getVendor()->getCreatedAt());
        $allTimeTitle = __(
            'All time: %1 - %2',
            $this->formatDate($vendorCreatedDate, \IntlDateFormatter::MEDIUM),
            $this->formatDate($today, \IntlDateFormatter::MEDIUM)
        );
        return [
            'today' => ['label' => __('Today'), 'title' => $dateToday, 'value' => $today.'_'.$endToday],
            'yesterday' => ['label' => __('Yesterday'), 'title' => $yesterdayTitle, 'value' => $yesterday.'_'.$endYesterday],
            'this-week-sun-today' => ['label' => __('This week (Sun - Today)'), 'title' => $thisWeekSunTodayTitle, 'value' => $thisWeekSun.'_'.$today],
            'this-week-mon-today' => ['label' => __('This week (Mon - Today)'), 'title' => $thisWeekMonTodayTitle, 'value' => $thisWeekMon.'_'.$today],
            'last-7days' => ['label' => __('Last 7 days'), 'title' => $last7DaysTitle, 'value' => $last7Days.'_'.$today],
            'last-week-sun-sat' => ['label' => __('Last week (Sun - Sat)'), 'title' => $lastWeekSunSatTitle, 'value' => $lastWeekSun.'_'.$lastWeekSat],
            'last-week-mon-sun' => ['label' => __('Last week (Mon - Sun)'), 'title' => $lastWeekMonSunTitle, 'value' => $lastWeekMon.'_'.$lastWeekSun],
            'this-month' => ['label' => __('This month'), 'title' => $thisMonthTitle, 'value' => $firstDayThisMonth.'_'.$today],
            'last-30days' => ['label' => __('Last 30 days'), 'title' => $last30DaysTitle, 'value' => $last30Days.'_'.$today],
            'last-month' => ['label' => __('Last month'), 'title' => $lastMonthTitle, 'value' => $firstDayLastMonth.'_'.$lastDayLastMonth],
            'all-time' => ['label' => __('All time'), 'title' => $allTimeTitle, 'value' => $vendorCreatedDate.'_'.$today],
        ];
    }

    /**
     * Get current selected date range
     *
     * @return array
     */
    public function getCurrentDateRange()
    {
        //$dateRangeType = $this->_vendorSession->getData('vendor_report_date_range');
        /*In the default select this month date range*/
        $dateRangeType = $this->_defaultDateRangeType?$this->_defaultDateRangeType:self::DEFAULT_DATE_RANGE;

        $dateRanges = $this->getDateRanges();
        $currentDateRange = $dateRanges[$dateRangeType];
        $currentDateRange['type'] = $dateRangeType;

        return $currentDateRange;
    }

    /**
     * Process js layout
     */
    public function processJsLayout()
    {
        $this->jsLayout['components']['reports']['date_range'] = $this->getCurrentDateRange();
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->processJsLayout();

        foreach ($this->_layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return \Laminas\Json\Json::encode($this->jsLayout);
    }
}
