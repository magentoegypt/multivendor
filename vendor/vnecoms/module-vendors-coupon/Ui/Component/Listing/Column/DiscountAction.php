<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Price
 */
class DiscountAction extends Column implements OptionSourceInterface
{
    /**
     * @var \Vnecoms\VendorsCoupon\Helper\Data $helperData
     */
    protected $_helperData;


    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Vnecoms\VendorsCoupon\Helper\Data $helperData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Vnecoms\VendorsCoupon\Helper\Data $helperData,
        array $components = [],
        array $data = []
    ) {
        $this->_helperData = $helperData;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $listActions = $this->_helperData->getDiscountActions();
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $action = isset($listActions[$item[$this->getData('name')]]) ? $listActions[$item[$this->getData('name')]] : [];
                if ($action) {
                  $item[$this->getData('name')] = $action['name'];
                }
            }
        }
        return $dataSource;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function toOptionArray()
    {
        $data = [];
        $listActions = $this->_helperData->getDiscountActions();
        foreach ($listActions as $key => $action) {
            $data[] = ['label' => __($action["name"]), 'value' => $key];
        }
        return $data;
    }
}
