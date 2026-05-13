<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Rule\Product;

class Chooser extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::index';
    /**
     * Prepare block for chooser.
     */
    public function execute()
    {
        $request = $this->getRequest();

        switch ($request->getParam('attribute')) {
            case 'sku':
                $block = $this->_view->getLayout()->createBlock(
                    'Vnecoms\VendorsCms\Block\Rule\Product\Chooser\Sku',
                    'rule_chooser_sku',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                );
                break;

            case 'category_ids':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int) $id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }

                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }

                $block = $this->_view->getLayout()->createBlock(
                    'Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree',
                    'promo_widget_chooser_category_ids',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                )->setTemplate('Vnecoms_VendorsCms::rule/category/checkboxes/tree.phtml')->setCategoryIds(
                    $ids
                );
                break;
            case 'ves_category_ids':
                $ids = $request->getParam('selected', []);
                if (is_array($ids)) {
                    foreach ($ids as $key => &$id) {
                        $id = (int) $id;
                        if ($id <= 0) {
                            unset($ids[$key]);
                        }
                    }

                    $ids = array_unique($ids);
                } else {
                    $ids = [];
                }

                $block = $this->_view->getLayout()->createBlock(
                    'Vnecoms\VendorsCategory\Block\Vendors\Category\Checkboxes\Tree',
                    'promo_widget_chooser_ves_category_ids',
                    ['data' => ['js_form_object' => $request->getParam('form')]]
                )->setTemplate('Vnecoms_VendorsCms::rule/category/checkboxes/tree.phtml')->setCategoryIds(
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
