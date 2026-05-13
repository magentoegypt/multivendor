<?php
namespace Vnecoms\VendorsRMA\Ui\Component;


class DataProvider extends \Vnecoms\RMA\Ui\Component\DataProvider
{
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
                if($paramName == "vendor_order_id"){
                    $paramName = "order_incremental_id";
                    $order =  \Magento\Framework\App\ObjectManager::getInstance()->get(
                        'Vnecoms\VendorsSales\Model\Order'
                    )->load($paramValue);
                    $paramValue = (string)$order->getOrder()->getIncrementId();
                }
                $check = explode(",",$paramValue);
                if(count($check) >= 2){
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('in')->create()
                    );
                }else{
                    $this->addFilter(
                        $this->filterBuilder->setField($paramName)->setValue($paramValue)->setConditionType('eq')->create()
                    );
                }
            }
        }
    }
}
