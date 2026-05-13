<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
//use Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery;


class Gallery extends \Magento\Backend\Block\Template implements  RendererInterface
//class Gallery extends \Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery implements  RendererInterface
{
    /**
     * Gallery field name suffix
     *
     * @var string
     */
    protected $fieldNameSuffix = 'product';

    /**
     * Gallery html id
     *
     * @var string
     */
    protected $htmlId = 'media_gallery';

    /**
     * Gallery name
     *
     * @var string
     */
    protected $name = 'product[media_gallery]';

    /**
     * Html id for data scope
     *
     * @var string
     */
    protected $image = 'image';

    /**
     * @var string
     */
    protected $formName = 'product_form';

    /**
     * Show render html of element
     *
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->getElementHtml();
    }

    /**
     * Prepares content block
     *
     * @return string
     */
    public function getContentHtml()
    {
    
        /* @var $content \Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery\Content */
        $content = $this->_layout->createBlock('Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery\Content');
        $content->setId($this->getHtmlId() . '_content')->setElement($this);
        $galleryJs = $content->getJsObjectName();
        $content->getUploader()->getConfig()->setMegiaGallery($galleryJs);
        return $content->toHtml();
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $html = $this->getContentHtml();
        return $html;
    }

    /**
     * @return string
     */
    protected function getHtmlId()
    {
        return $this->htmlId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFieldNameSuffix()
    {
        return $this->fieldNameSuffix;
    }
}
