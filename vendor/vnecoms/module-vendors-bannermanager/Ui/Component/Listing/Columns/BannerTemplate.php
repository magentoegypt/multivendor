<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\BannerManager\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class BannerTemplate.
 */
class BannerTemplate extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var string[]
     */
    protected $banner;

    /** @var  \Vnecoms\BannerManager\Helper\Data */
    protected $helperData;


    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        //return parent::prepareDataSource($dataSource);
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $banner) {
                //var_dump($banner);die;
//                if ($banner['page_type'] == PageType::CUSTOM_WIDGET) {
//                    $banner['position'] = '';
//                }

                 //$banner['position'] = '';
            }
        }
        return $dataSource;
    }

}
