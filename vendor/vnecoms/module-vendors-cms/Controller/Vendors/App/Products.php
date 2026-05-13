<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\App;

class Products extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * Products chooser Action (Ajax request).
     */
    public function execute()
    {
        $selected = $this->getRequest()->getParam('selected', '');
        $productTypeId = $this->getRequest()->getParam('product_type_id', '');
        $chooser = $this->_view->getLayout()->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\Product\App\Chooser'
        )->setName(
            $this->mathRandom->getUniqueHash('products_grid_')
        )->setUseMassaction(
            true
        )->setProductTypeId(
            $productTypeId
        )->setSelectedProducts(
            explode(',', $selected)
        );

        /* @var $serializer \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Serializer */
        $serializer = $this->_view->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Grid\Serializer',
            '',
            [
                'data' => [
                    'grid_block' => $chooser,
                    'callback' => 'getSelectedProducts',
                    'input_element_name' => 'selected_products',
                    'reload_param_name' => 'selected_products',
                ],
            ]
        )->setTemplate('Vnecoms_Vendors::widget/grid/serializer.phtml');

        $this->setBody($chooser->toHtml().$serializer->toHtml());
    }
}
