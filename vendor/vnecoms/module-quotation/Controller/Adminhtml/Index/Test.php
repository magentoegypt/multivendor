<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Index;

class Test extends \Magento\Backend\App\Action
{


    /**
     * New action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->_objectManager->create('Vnecoms\Quotation\Model\Item')->load(11);
      //  $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load(1245);
       // $quote->addProduct($product);

        die();
    }
}
