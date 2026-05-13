<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model;

use Vnecoms\VendorsCms\Api\Data\PageInterface;
use Vnecoms\VendorsCms\Model\ResourceModel\Page as ResourceCmsPage;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Cms Page Model.
 *
 * @method ResourceCmsPage _getResource()
 * @method ResourceCmsPage getResource()
 * @method Page setStoreId(array $storeId)
 * @method array getStoreId()
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Page extends AbstractModel implements PageInterface, IdentityInterface
{
    /**
     * No route page id.
     */
    const NOROUTE_PAGE_ID = 'no-route';

    const XML_PATH_404 = 'cms/vendor_configs/404_page';

    const XML_PATH_HOME_PAGE = 'cms/vendor_configs/home_page';

    /**#@+
     * Page's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**#@-*/

    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'vendor_cms_page';

    /**
     * @var string
     */
    protected $_cacheTag = 'vendor_cms_page';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_cms_page';

    /**
     * @var \Vnecoms\VendorsCms\Helper\Data
     */
    protected $cmsHelper;

    protected $vendorSession;

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCms\Model\ResourceModel\Page');
    }

    /**
     * Load object data.
     *
     * @param int|null $id
     * @param string   $field
     *
     * @return $this
     */
    public function load($id, $field = null)
    {
        if ($id === null) {
            return $this->noRoutePage();
        }

        return parent::load($id, $field);
    }

    /**
     * Load No-Route Page.
     *
     * @return \Vnecoms\VendorsCms\Model\Page
     */
    public function noRoutePage()
    {
        return $this->load(self::NOROUTE_PAGE_ID, $this->getIdFieldName());
    }

    /**
     * Check if page identifier exist for specific store
     * return page id if page exists.
     *
     * @param string $identifier
     * @param int    $vendorId
     *
     * @return int
     */
    public function checkIdentifier($identifier, $vendorId)
    {
        return $this->_getResource()->checkIdentifier($identifier, $vendorId);
    }

    /**
     * Prepare page's statuses.
     * Available event vendor_cms_page_get_available_statuses to customize statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Get identities.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }

    /**
     * Get ID.
     *
     * @return int
     */
    public function getId()
    {
        return parent::getData(self::PAGE_ID);
    }

    public function getVendorId()
    {
        // TODO: Implement getVendorId() method.
        return parent::getData(self::VENDOR_ID);
    }

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getData(self::IDENTIFIER);
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Get page layout.
     *
     * @return string
     */
    public function getPageLayout()
    {
        return $this->getData(self::PAGE_LAYOUT);
    }

    /**
     * Get meta title.
     *
     * @return string|null
     */
    public function getMetaTitle()
    {
        return $this->getData(self::META_TITLE);
    }

    /**
     * Get meta keywords.
     *
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->getData(self::META_KEYWORDS);
    }

    /**
     * Get meta description.
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->getData(self::META_DESCRIPTION);
    }

    /**
     * Get content heading.
     *
     * @return string
     */
    public function getContentHeading()
    {
        return $this->getData(self::CONTENT_HEADING);
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Get creation time.
     *
     * @return string
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * Get update time.
     *
     * @return string
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Get sort order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * Get layout update xml.
     *
     * @return string
     */
    public function getLayoutUpdateXml()
    {
        return $this->getData(self::LAYOUT_UPDATE_XML);
    }

    /**
     * Get custom theme.
     *
     * @return string
     */
    public function getCustomTheme()
    {
        return $this->getData(self::CUSTOM_THEME);
    }

    /**
     * Get custom root template.
     *
     * @return string
     */
    public function getCustomRootTemplate()
    {
        return $this->getData(self::CUSTOM_ROOT_TEMPLATE);
    }

    /**
     * Get custom layout update xml.
     *
     * @return string
     */
    public function getCustomLayoutUpdateXml()
    {
        return $this->getData(self::CUSTOM_LAYOUT_UPDATE_XML);
    }

    /**
     * Get custom theme from.
     *
     * @return string
     */
    public function getCustomThemeFrom()
    {
        return $this->getData(self::CUSTOM_THEME_FROM);
    }

    /**
     * Get custom theme to.
     *
     * @return string
     */
    public function getCustomThemeTo()
    {
        return $this->getData(self::CUSTOM_THEME_TO);
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Set ID.
     *
     * @param int $id
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setId($id)
    {
        return $this->setData(self::PAGE_ID, $id);
    }

    public function setVendorId($id)
    {
        // TODO: Implement setVendorId() method.
        return $this->setData(self::VENDOR_ID, $id);
    }

    /**
     * Set identifier.
     *
     * @param string $identifier
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setIdentifier($identifier)
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set page layout.
     *
     * @param string $pageLayout
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setPageLayout($pageLayout)
    {
        return $this->setData(self::PAGE_LAYOUT, $pageLayout);
    }

    /**
     * Set meta title.
     *
     * @param string $metaTitle
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setMetaTitle($metaTitle)
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * Set meta keywords.
     *
     * @param string $metaKeywords
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setMetaKeywords($metaKeywords)
    {
        return $this->setData(self::META_KEYWORDS, $metaKeywords);
    }

    /**
     * Set meta description.
     *
     * @param string $metaDescription
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setMetaDescription($metaDescription)
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
    }

    /**
     * Set content heading.
     *
     * @param string $contentHeading
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setContentHeading($contentHeading)
    {
        return $this->setData(self::CONTENT_HEADING, $contentHeading);
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Set creation time.
     *
     * @param string $creationTime
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCreationTime($creationTime)
    {
        return $this->setData(self::CREATION_TIME, $creationTime);
    }

    /**
     * Set update time.
     *
     * @param string $updateTime
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setUpdateTime($updateTime)
    {
        return $this->setData(self::UPDATE_TIME, $updateTime);
    }

    /**
     * Set sort order.
     *
     * @param string $sortOrder
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * Set layout update xml.
     *
     * @param string $layoutUpdateXml
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setLayoutUpdateXml($layoutUpdateXml)
    {
        return $this->setData(self::LAYOUT_UPDATE_XML, $layoutUpdateXml);
    }

    /**
     * Set custom theme.
     *
     * @param string $customTheme
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCustomTheme($customTheme)
    {
        return $this->setData(self::CUSTOM_THEME, $customTheme);
    }

    /**
     * Set custom root template.
     *
     * @param string $customRootTemplate
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCustomRootTemplate($customRootTemplate)
    {
        return $this->setData(self::CUSTOM_ROOT_TEMPLATE, $customRootTemplate);
    }

    /**
     * Set custom layout update xml.
     *
     * @param string $customLayoutUpdateXml
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCustomLayoutUpdateXml($customLayoutUpdateXml)
    {
        return $this->setData(self::CUSTOM_LAYOUT_UPDATE_XML, $customLayoutUpdateXml);
    }

    /**
     * Set custom theme from.
     *
     * @param string $customThemeFrom
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCustomThemeFrom($customThemeFrom)
    {
        return $this->setData(self::CUSTOM_THEME_FROM, $customThemeFrom);
    }

    /**
     * Set custom theme to.
     *
     * @param string $customThemeTo
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setCustomThemeTo($customThemeTo)
    {
        return $this->setData(self::CUSTOM_THEME_TO, $customThemeTo);
    }

    /**
     * Set is active.
     *
     * @param int|bool $isActive
     *
     * @return \Vnecoms\VendorsCms\Api\Data\PageInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        $originalIdentifier = $this->getOrigData('identifier');
        $currentIdentifier = $this->getIdentifier();

        if (!$this->getId() || $originalIdentifier === $currentIdentifier) {
            return parent::beforeSave();
        }

        return parent::beforeSave();
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function beforeDelete()
    {
        return parent::beforeSave();
    }

    /**
     * Get CMS Helper.
     *
     * @return \Vnecoms\VendorsCms\Helper\Data
     */
    public function getCmsHelper()
    {
        if ($this->cmsHelper == null) {
            $this->cmsHelper = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\VendorsCms\Helper\Data');
        }

        return $this->cmsHelper;
    }

    /**
     * Get current vendor model.
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->getRegisterVendor()) return $this->getRegisterVendor();
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }
}
