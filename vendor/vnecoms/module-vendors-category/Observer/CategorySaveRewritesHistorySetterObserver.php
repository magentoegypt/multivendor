<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Framework\Event\ObserverInterface;

/**
 * event for prepare save, see in Save Action
 * Class CategorySaveRewritesHistorySetterObserver.
 */
class CategorySaveRewritesHistorySetterObserver implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return;
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        $data = $observer->getEvent()->getRequest()->getPostValue();

        /*
         * Create Permanent Redirect for old URL key
         */
        if ($category->getId() && isset($data['url_key_create_redirect'])) {
            $category->setData('save_rewrites_history', (bool) $data['url_key_create_redirect']);
        }
        //var_dump($category->getData());die();
    }
}
