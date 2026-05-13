<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Wysiwyg\Images;

/**
 * Directory tree renderer for Cms Wysiwyg Images.
 *
 * @author     Ves Team <core@magentocommerce.com>
 */
class Tree extends \Magento\Backend\Block\Template
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $vendorSession;

    /**
     * Cms wysiwyg images.
     *
     * @var \Vnecoms\VendorsCms\Helper\Wysiwyg\Images
     */
    protected $_cmsWysiwygImages = null;

    /**
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Vnecoms\VendorsCms\Helper\Wysiwyg\Images $cmsWysiwygImages
     * @param \Magento\Framework\Registry               $registry
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\VendorsCms\Helper\Wysiwyg\Images $cmsWysiwygImages,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_cmsWysiwygImages = $cmsWysiwygImages;
        parent::__construct($context, $data);
    }

    /**
     * Json tree builder.
     *
     * @return string
     */
    public function getTreeJson()
    {
        $vendorRoot = $this->_cmsWysiwygImages->getVendorRoot();
        $collection = $this->_coreRegistry->registry(
            'storage'
        )->getDirsCollection(
            $this->_cmsWysiwygImages->getCurrentPath()
        );
        $jsonArray = [];
        foreach ($collection as $item) {
            $jsonArray[] = [
                'text' => $this->_cmsWysiwygImages->getShortFilename($item->getBasename(), 20),
                'id' => $this->_cmsWysiwygImages->convertPathToId($item->getFilename()),
                'path' => substr($item->getFilename(), strlen($vendorRoot)),
                'cls' => 'folder',
            ];
        }

        return \Laminas\Json\Json::encode($jsonArray);
    }

    /**
     * Json source URL.
     *
     * @return string
     */
    public function getTreeLoaderUrl()
    {
        return $this->getUrl('cms/*/treeJson');
    }

    /**
     * Vendor Root node name of tree.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getRootNodeName()
    {
        if ($this->getVendor()->getId()) {
            $vendorId = $this->getVendor()->getVendorId();

            return __("$vendorId Root");
        }

        return __('Vendor Root');
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

    /**
     * Return tree node full path based on current path.
     *
     * @return string
     */
    public function getTreeCurrentPath()
    {
        $treePath = ['root'];
        if ($path = $this->_coreRegistry->registry('storage')->getSession()->getCurrentPath()) {
            $path = str_replace($this->_cmsWysiwygImages->getVendorRoot(), '', $path);
            $relative = [];
            foreach (explode('/', $path) as $dirName) {
                if ($dirName) {
                    $relative[] = $dirName;
                    $treePath[] = $this->_cmsWysiwygImages->idEncode(implode('/', $relative));
                }
            }
        }

        return $treePath;
    }

    /**
     * Get tree widget options.
     *
     * @return array
     */
    public function getTreeWidgetOptions()
    {
        return [
            'CmsfolderTree' => [
                'rootName' => $this->getRootNodeName(),
                'url' => $this->getTreeLoaderUrl(),
                'currentPath' => array_reverse($this->getTreeCurrentPath()),
            ],
        ];
    }
}
