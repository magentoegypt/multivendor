<?php

namespace Vnecoms\BannerManager\Helper;

use Vnecoms\BannerManager\Model\Slider;
/**
 * Class Data Helper for common use in block classes
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * category collection factory.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /** @var  \Vnecoms\BannerManager\Model\Slider */
    protected $sliderModel;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_configHelper;

    /**
     * @var
     */
    protected $vendorSession;


    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param Slider $slider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Vnecoms\BannerManager\Model\Slider $slider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->_storeManager = $storeManager;
        $this->sliderModel = $slider;
        $this->_configHelper = $configHelper;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * get Base Url Media.
     *
     * @param string $path   [description]
     * @param bool   $secure [description]
     * @return string [description]
     */
    public function getBaseUrlMedia($path = '', $secure = false)
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, $secure) .'ves/bannermanager/images'.$path;
    }

    /**
     * get Backend Url
     * @param  string $route
     * @param  array  $params
     * @return string
     */
    public function getBackendUrl($route = '', $params = ['_current' => true])
    {
        return $this->_backendUrl->getUrl($route, $params);
    }


    /**
     * get categories array.
     *
     * @return array
     */
    public function getCategoriesArray()
    {
        $categoriesArray = $this->_categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('path', 'asc')
            ->load()
            ->toArray();

        $categories = array();
        foreach ($categoriesArray as $categoryId => $category) {
            if (isset($category['name']) && isset($category['level'])) {
                $categories[] = array(
                    'label' => $category['name'],
                    'level' => $category['level'],
                    'value' => $categoryId,
                );
            }
        }

        return $categories;
    }


    /**
     * @return array
     */
    public function getSliderType()
    {
        return $this->sliderModel->toOptionArray();
    }

    public function getSliderType2()
    {

        return [
            [
                'label' => __('--------- Please choose slider type -------'),
                'value' => '',
            ],
            /*[
                'label' => __('Special Slider'),
                'value' => [
                    ['value' => Slider::STYLESLIDE_POPUP, 'label' => __('Pop up on Home page')],
                    ['value' => Slider::STYLESLIDE_SPECIAL_NOTE, 'label' => __('Note displayed on all pages')],
                ],
            ],*/
            /*[
                'label' => __('Unresponsive Slider'),
                'value' => [
                    [
                        'label' => __('Slider Evolution Default'),
                        'value' => Slider::STYLESLIDE_EVOLUTION_ONE,
                    ],
                    [
                        'label' => __('Slider Evolution Caborno'),
                        'value' => Slider::STYLESLIDE_EVOLUTION_TWO,
                    ],
                    [
                        'label' => __('Slider Evolution Minimalist'),
                        'value' => Slider::STYLESLIDE_EVOLUTION_THREE,
                    ],
                    [
                        'label' => __('Slider Evolution Fresh'),
                        'value' => Slider::STYLESLIDE_EVOLUTION_FOUR,
                    ],
                ],
            ],*/
            [
                'label' => __('FlexSlider'),
                'value' => [
                    [
                        'label' => __('Default'),
                        'value' => Slider::SLIDE_FLEX_MODE_DEFAULT,
                    ],
                    /*[
                        'label' => __('FlexSlider 2'),
                        'value' => Slider::STYLESLIDE_FLEXSLIDER_TWO,
                    ],
                    [
                        'label' => __('FlexSlider 3'),
                        'value' => Slider::STYLESLIDE_FLEXSLIDER_THREE,
                    ],
                    [
                        'label' => __('FlexSlider 4'),
                        'value' => Slider::STYLESLIDE_FLEXSLIDER_FOUR,
                    ],*/
                ],
            ],
        ];

    }

    public function getConfig($key, $store = null)
    {
        $result = $this->scopeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store);

        return $result;
    }

    public function getVendorsCmsHomePage($vendorId)
    {
        return $this->_configHelper->getVendorConfig('cms/vendor_configs/home_page', $vendorId);
    }

    /**
     * @param int $vendorId
     * @return mixed
     */
    public function getBannerTypeConfig($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/banner_type', $vendorId);
    }

    /**
     * @param int $vendorId
     * @return mixed
     */
    public function getBannerIdFromConfig($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/banner_id', $vendorId);
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    protected function getVendor()
    {
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

    /**
     * Get Template of Sliders
     *
     * @return array
     */
    public function getSliderTemplate()
    {
        return [

                    [
                        'value' => 'default.phtml',
                        'label' => __('Swipe Default')
                    ],
                    [
                        'value' => 'responsive.phtml',
                        'label' => __('Swipe Responsive')
                    ],
                    /*[
                        'value' => 'vertical.phtml',
                        'label' => __('Vertical')
                    ],
                    [
                        'value' => 'thumbs-gallery.phtml',
                        'label' => __('Thumbnails')
                    ],*/
                    /*[
                        'value' => 'parallax.phtml',
                        'label' => __('Swipe Parallax')
                    ],*/
                    /*[
                        'value' => 'autoheight.phtml',
                        'label' => __('Auto content height')
                    ],
                    [
                        'value' => 'freemode.phtml',
                        'label' => __('Free mode')
                    ],*/
                    [
                        'value' => 'rtl.phtml',
                        'label' => __('Swipe Right to left')
                    ],
                   /* [
                        'value' => 'pagination_process.phtml',
                        'label' => __('Pagination process')
                    ],
                    [
                        'value' => 'lazy_load.phtml',
                        'label' => __('Lazy load template')
                    ]*/




        ];
    }

    public function getEffect()
    {
        return  [
            [
                'label'     => __('--Please choose effect--'),
                'value'     => '',
            ],
            [
                'label'     => __('Fade'),
                'value'     => 'fade',
            ],
            [
                'label'     => __('Slide'),
                'value'     => 'slide',
            ],
            [
                'label'     => __('Cube'),
                'value'     => 'cube',
            ],
            [
                'label'     => __('Coverflow'),
                'value'     => 'coverflow',
            ],
            [
                'label'     => __('Flip'),
                'value'     => 'flip',
            ],
        ];

    }

}
