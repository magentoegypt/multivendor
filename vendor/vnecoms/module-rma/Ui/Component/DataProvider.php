<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Ui\Component;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\Api\Search\SearchResultInterface;
use Vnecoms\RMA\Model\Request;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        if (!isset($this->data['config']['filter_url_params'])) {
            return;
        }


        foreach ($this->data['config']['filter_url_params'] as $paramName => $paramValue) {
            if ('*' == $paramValue) {
                $paramValue = $this->request->getParam($paramName);
            }

            if ($paramValue) {
                $this->data['config']['update_url'] = sprintf(
                    '%s%s/%s',
                    $this->data['config']['update_url'],
                    $paramName,
                    $paramValue
                );

                if ($paramName == "order_id") {
                    $paramName = "order_incremental_id";
                    $order =  \Magento\Framework\App\ObjectManager::getInstance()->get(
                        'Magento\Sales\Model\Order'
                    )->load($paramValue);
                    $paramValue = $order->getIncrementId();
                }

                $check = explode(",", $paramValue);
                if (count($check) >= 2) {
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('in')->create()
                    );
                } else {
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('eq')->create()
                    );
                }
            }
            if ($paramName == 'state') {
                $paramValue = [Request::STATE_CANCELED,Request::STATE_CLOSED];
                $this->addFilter(
                    $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('nin')->create()
                );
            }
        }
    }

    /**
     * @param SearchResultInterface $searchResult
     * @return array
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];

        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $itemData = [];
            foreach ($item->getCustomAttributes() as $attribute) {
                $itemData[$attribute->getAttributeCode()] = $attribute->getValue();
            }
            //unset($itemData["rma_label"]);
            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }
}
