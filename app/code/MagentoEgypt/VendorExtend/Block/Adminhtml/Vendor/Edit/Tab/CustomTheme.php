<?php
namespace MagentoEgypt\VendorExtend\Block\Adminhtml\Vendor\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\UrlInterface;
use Vnecoms\VendorsCustomTheme\Model\Theme;

class CustomTheme extends Generic implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'MagentoEgypt_VendorExtend::vendor/edit/tab/custom_theme.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Vnecoms\VendorsCustomTheme\Helper\Data
     */
    protected $_themeHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\CollectionFactory $collectionFactory
     * @param \Vnecoms\VendorsCustomTheme\Helper\Data $themeHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\CollectionFactory $collectionFactory,
        \Vnecoms\VendorsCustomTheme\Helper\Data $themeHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_collectionFactory = $collectionFactory;
        $this->_themeHelper = $themeHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Custom Theme');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Custom Theme');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get the current vendor model from registry.
     *
     * @return \Vnecoms\Vendors\Model\Vendor|null
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }

    /**
     * Get enabled themes collection.
     *
     * @return \Vnecoms\VendorsCustomTheme\Model\ResourceModel\Theme\Collection
     */
    public function getCollection()
    {
        if (!$this->getData('collection')) {
            $collection = $this->_collectionFactory->create()
                ->addFieldToFilter('status', Theme::STATUS_ENABLE);
            $this->setData('collection', $collection);
        }
        return $this->getData('collection');
    }

    /**
     * Get currently selected theme id for this vendor.
     *
     * @return string
     */
    public function getSelectedThemeId()
    {
        $vendor = $this->getVendor();
        if (!$vendor || !$vendor->getId()) {
            return '';
        }
        return (string)$this->_themeHelper->getVendorTheme($vendor);
    }

    /**
     * Build absolute URL for a preview image stored in media.
     *
     * @param string $image
     * @return string
     */
    public function getPreviewImageUrl($image)
    {
        return $this->_urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . $image;
    }

    /**
     * @return Form
     */
    protected function _prepareForm()
    {
        return parent::_prepareForm();
    }
}
