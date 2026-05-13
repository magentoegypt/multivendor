<?php

namespace Vnecoms\RMA\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class Price
 */
class Rma extends Column
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @return float
     */
    protected function getRmaData($orderId)
    {
        $collection = $this->objectManager->create('Vnecoms\RMA\Model\ResourceModel\Request\Collection')
            ->addFieldToFilter('order_incremental_id', $orderId);
        $result = [];
        $statusArr = $this->objectManager->create('Vnecoms\RMA\Model\Status')->getOptionArray();
        foreach($collection as $request){
            $url = $this->urlBuilder->getUrl('vrma/request/view',['request_id' => $request->getId()]);
            $requestClass = 'rma-link-status-'.$request->getStatus();
            $result[] = '<div class="'.$requestClass.'"><a target="_blank" href="'.$url.'">'.$request->getIncrementId().'</a> - <span>'.$statusArr[$request->getStatus()].'</span></div>';
        }
        
        return implode("", $result);
    }
    
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $orderId = $item['increment_id'];
                $item[$this->getData('name')] = $this->getRmaData($orderId);
            }
        }

        return $dataSource;
    }
}
