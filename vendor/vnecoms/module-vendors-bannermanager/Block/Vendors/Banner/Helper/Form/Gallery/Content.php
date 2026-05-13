<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery;

use Magento\Framework\UrlInterface;

class Content extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery\Content
{

    /** @var \Vnecoms\BannerManager\Model\ResourceModel\Item\CollectionFactory  */
    protected $itemCollectionFactory;

    /** @var \Magento\Framework\UrlInterface  */
    protected $_url;

    /**
     * @var string
     */
    protected $_template = 'Vnecoms_BannerManager::banner/helper/gallery.phtml';

    protected $vendorSession;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Vnecoms\BannerManager\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory,
        array $data = []
    ){
        parent::__construct($context, $jsonEncoder, $mediaConfig, $data);
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->_url = $context->getUrlBuilder();
    }

    /**
     * Retrieve media attributes
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        return ;
        //return $this->getElement()->getDataObject()->getMediaAttributes();
    }

    /**
     * Get image types data
     *
     * @return array
     */
    public function getImageTypes()
    {
        $ImageTypes = [];
        $ImageTypes = [
            'swatch_image' => [
                'code' => 'swatch_image',
                'value' => '',
                'label' => __('Swatch Image'),
                'scope' => __('[STORE VIEW]'),
                'name' => __('product[swatch_image]')
            ],
            'image' => [
                'code' => 'image',
                'value' => '',
                'label' => __('Base Image'),
                'scope' => __('[STORE VIEW]'),
                'name' => __('product[image]')
            ],
            'small_image' => [
                'code' => 'small_image',
                'value' => '',
                'label' => 'Small Image',
                'scope' => __('[STORE VIEW]'),
                'name' => __('product[small_image]')
            ],
            'thumbnail' => [
                'code' => 'thumbnail',
                'value' => '',
                'label' => 'Thumbnail',
                'scope' => __('[STORE VIEW]'),
                'name' => __('product[thumbnail]')
            ]
        ];
        return $ImageTypes;
    }

    /**
     * Get all images of item
     *
     * @return array
     */
    public function getImagesItemCollection()
    {
        $bannerId = $this->getRequest()->getParam('banner_id');
        if (!isset($bannerId)) {
            return [];
        }

        // Get Resource Collection
        /** @var \Vnecoms\BannerManager\Model\ResourceModel\Item\Collection $itemResourceModel */
        $itemResourceModel = $this->itemCollectionFactory->create();
        /** @var array $itemCollection */
        $itemCollection = $itemResourceModel->addFieldToFilter('banner_id', $bannerId)
            ->setOrder('sort_order', 'ASC')->getData();

        $items = [];
        foreach ($itemCollection as $item) {
            $items[] = $item;
        }

        return $items;
    }

    public function getImagesJson()
    {
        /** @var array $collection */
        $collection = $this->getImagesItemCollection();
        if (is_array($collection)) {
            $value  = $collection;

            if (sizeof($value)) {
                $images = [];
                foreach ($value as $key => $image) {
                    $images[$key]['item_id']                = $image['item_id'];
                    $images[$key]['file']                   = $image['filename'];
                    $images[$key]['media_type']             = 'image';
                    $images[$key]['entity_id']              = '1';
                    $images[$key]['label']                  = $image['alt'];
                    $images[$key]['position']               = $image['sort_order'];
                    $images[$key]['disabled']               = '0';
                    //$images[$key]['label_default']          = $image['filename_alt'];
                    $images[$key]['disabled_default']       = '0';
                    $images[$key]['title']                  = $image['title'];
                    $images[$key]['alt']                    = $image['alt'];
                    $images[$key]['item_url']               = $image['url'];
                    $images[$key]['item_short_description'] = $image['short_description'];
                    $images[$key]['item_status']            = $image['status'];
//                    $images[$key]['from_date']              = $this->_localeDate->date($image['from_date'])
//                        ->setTimezone(new \DateTimeZone('UTC'))->format('m/d/Y g:i a');
//                    $images[$key]['to_date']                = $this->_localeDate->date($image['to_date'])
//                        ->setTimezone(new \DateTimeZone('UTC'))->format('m/d/Y g:i a');
                    $images[$key]['url'] = $this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                        .'ves/bannermanager/images'.$image['filename'];
                }

                return $this->_jsonEncoder->encode($images);
            }
        }
        return '[]';
    }

    /**
     * @return AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->addChild('uploader', 'Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery\Uploader');

        $this->getUploader()->getConfig()->setUrl(
            $this->_urlBuilder->getUrl('bannermanager/banner_gallery/upload')
        )->setFileField(
            'image'
        )->setFilters(
            [
                'images' => [
                    'label' => __('Images (.gif, .jpg, .png)'),
                    'files' => ['*.gif', '*.jpg', '*.jpeg', '*.png'],
                ],
            ]
        );

        //$this->_eventManager->dispatch('catalog_product_gallery_prepare_layout', ['block' => $this]);

        return \Magento\Framework\View\Element\AbstractBlock::_prepareLayout();
    }
}
