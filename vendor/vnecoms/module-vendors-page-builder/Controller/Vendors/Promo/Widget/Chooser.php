<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\Promo\Widget;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;

class Chooser extends Action
{
    /**
     * Prepare block for chooser
     *
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();

        switch ($request->getParam('attribute')) {
            case 'sku':
                $block = $this->_view->getLayout()->createBlock(
                    \Vnecoms\VendorsPageBuilder\Block\Vendors\Widget\Chooser\Sku::class,
                    'promo_widget_chooser_sku',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                );
                break;

            case 'category_ids':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int)$id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }

                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }

                $block = $this->_view->getLayout()->createBlock(
                    \Vnecoms\VendorsPageBuilder\Block\Vendors\Category\Checkboxes\Tree::class,
                    'promo_widget_chooser_category_ids',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                )->setCategoryIds(
                    $ids
                );
                break;

            default:
                $block = false;
                break;
        }

        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
