<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 30/06/2016
 * Time: 17:28
 */

namespace Vnecoms\BannerManager\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Vnecoms\BannerManager\Block\Vendors\Banner\Grid\Renderer\Action\UrlBuilder;

class BannerActions extends Column
{
    const BANNER_URL_PATH_EDIT   = 'bannermanager/banner/edit';
    const BANNER_URL_PATH_DELETE = 'bannermanager/banner/delete';
    const BANNER_URL_PATH_PREVIEW = 'bannermanager/banner/preview';

    /** @var UrlInterface  */
    protected $urlBuilder;

    /** @var UrlBuilder  */
    protected $actionUrlBuilder;

    /** @var string  */
    protected $editUrl;

    /**
     * BannerActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param UrlBuilder $actionUrlBuilder
     * @param array $components
     * @param array $data
     * @param string $editUrl
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        UrlBuilder $actionUrlBuilder,
        array $components = [],
        array $data = [],
        $editUrl = self::BANNER_URL_PATH_EDIT
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->actionUrlBuilder = $actionUrlBuilder;
        $this->editUrl = $editUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['banner_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl($this->editUrl, ['banner_id' => $item['banner_id']]),
                        'label' => __('Edit')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl(self::BANNER_URL_PATH_DELETE, [
                            'banner_id' => $item['banner_id']
                        ]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete ${ $.$data.title }'),
                            'message' => __('Are you sure you wan\'t to delete a ${ $.$data.title } record?')
                        ]
                    ];
                }

                /*if (isset($item['identifier'])) {
                    $item[$name]['preview'] = [
                        'href' => $this->actionUrlBuilder->getUrl(
                            $item['identifier'],
                            isset($item['_first_store_id']) ? $item['_first_store_id'] : null,
                            isset($item['store_code']) ? $item['store_code'] : null
                        ),
                        'label' => __('View')
                    ];
                }*/
            }
        }
        return $dataSource;
    }
}