<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Ui\Component\Grid\Request;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Priority
 */
class Order extends Column
{

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $order = \Magento\Framework\App\ObjectManager::getInstance()->get(
                        'Magento\Sales\Model\Order'
                    )->loadByIncrementId($item['order_incremental_id']);
                  // $urlOrder = "<a href='".$this->urlBuilder->getUrl("sales/order/view", array("order_id"=>$order->getId()))."' target='_blank'>".$item[$this->getData('name')]."</a>";

                    $item[$this->getData('name')."_action"] = $this->urlBuilder->getUrl("sales/order/view", ["order_id"=>$order->getId()]);
                }
            }
        }

        return $dataSource;
    }
}
