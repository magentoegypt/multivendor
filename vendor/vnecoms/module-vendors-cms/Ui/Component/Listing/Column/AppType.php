<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class AppType.
 */
class AppType extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var
     */
    protected $app;

    /**
     * @var \Vnecoms\VendorsCms\Model\App\Config
     */
    protected $appConfig;

    /**
     * @param ContextInterface                $context
     * @param UiComponentFactory              $uiComponentFactory
     * @param \Magento\Catalog\Helper\Image   $imageHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array                           $components
     * @param array                           $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Vnecoms\VendorsCms\Model\App\Config $appConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->appConfig = $appConfig;
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
            $apps = $this->appConfig->getApps();
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $code = $item['type'];
                $type = $this->getApp()
                    ->getAppsConfigOptionArray();
                $appInfo = $this->appConfig->getAppByClassType($item['type']);
                $type = $appInfo['name'];
                $item['type'] = $type;
            }
        }

        return $dataSource;
    }

    /**
     * @return mixed
     */
    public function getApp()
    {
        if (!$this->app) {
            $this->app = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\VendorsCms\Model\App');
        }

        return $this->app;
    }
}
