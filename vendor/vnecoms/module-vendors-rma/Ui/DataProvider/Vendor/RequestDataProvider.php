<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Ui\DataProvider\Vendor;

use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory;
use Vnecoms\VendorsRMA\Model\Request;

/**
 * Class RequestDataProvider
 */
class RequestDataProvider extends \Vnecoms\RMA\Ui\Component\DataProvider
{
    /**
     * @return void
     */
    protected function prepareUpdateUrl()
    {
        $paramValue = array(Request::STATE_AWAITING,Request::STATE_BEING);
        $this->addFilter(
            $this->filterBuilder->setField('state')->setValue($paramValue)->setConditionType('in')->create()
        );
        /*
        $this->addFilter(
            $this->filterBuilder->setField('vendor_id')->setValue(null)->create()
        ); */
    }


}
