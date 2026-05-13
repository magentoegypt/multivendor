<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Framework\App\Action\Action;

class Test extends Action
{
    public function execute()
    {
        $quote = $this->_objectManager->get('Vnecoms\Quotation\Model\Quote')->load(82);
       // $proposal = $this->_objectManager->get('Vnecoms\Quotation\Model\Proposal')->load(52);
        $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load(291);
        $params = new \Magento\Framework\DataObject([
            'qty' => 1,
            'product' => 1008,
            'selected_configurable_option' => 1007,
            'super_attribute' => [135 => 6]
        ]);

        try {
            $quote->addProduct($product, $params);

            $quote->collectTotals()->save();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

       // var_dump($proposal->getConvertedPrice());
    }
}