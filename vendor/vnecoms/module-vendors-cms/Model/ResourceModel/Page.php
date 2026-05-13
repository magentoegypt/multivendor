<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel;

use Vnecoms\VendorsCms\Model\Page as CmsPage;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\Store;
use Magento\Framework\EntityManager\EntityManager;
use Vnecoms\VendorsCms\Api\Data\PageInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Vnecoms\VendorsCms\Model\Rewrite\CmsPageUrlPathGenerator;
use Vnecoms\VendorsCms\Model\Rewrite\CmsPageUrlRewriteGenerator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Cms page mysql resource.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Page extends AbstractDb
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Rewrite\CmsPageUrlPathGenerator
     */
    protected $cmsPageUrlPathGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    public function __construct(
        Context $context,
        DateTime $dateTime,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        CmsPageUrlPathGenerator $cmsPageUrlPathGenerator,
        UrlPersistInterface $urlPersist,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dateTime = $dateTime;
        $this->cmsPageUrlPathGenerator = $cmsPageUrlPathGenerator;
        $this->urlPersist = $urlPersist;
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('vnecoms_vendor_cms_page', 'page_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(PageInterface::class)->getEntityConnection();
    }

    /**
     * Process page data before saving.
     *
     * @param AbstractModel $object
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /*
         * For two attributes which represent timestamp data in DB
         * we should make converting such as:
         * If they are empty we need to convert them into DB
         * type NULL so in DB they will be empty and not some default value
         */
        foreach (['custom_theme_from', 'custom_theme_to'] as $field) {
            $value = !$object->getData($field) ? null : $this->dateTime->formatDate($object->getData($field));
            $object->setData($field, $value);
        }

        // Generate new identifier if field identifier null
        $urlKey = $object->getData('identifier');
        if ($urlKey === '' || $urlKey === null) {
            $object->setData('identifier', $this->cmsPageUrlPathGenerator->generateUrlKey($object));
        }

        if (!$this->isValidPageIdentifier($object)) {
            throw new LocalizedException(
                __('The page URL key contains capital letters or disallowed symbols.')
            );
        }

        if ($this->isNumericPageIdentifier($object)) {
            throw new LocalizedException(
                __('The page URL key cannot be made of only numbers.')
            );
        }

        /*
         * check unique page identity from a vendor
         */
        if ($errors = $this->IsUniquePageToVendor($object)) {

            foreach ($errors as $error){
                throw new LocalizedException($error);
            }
        }
        return parent::_beforeSave($object);
    }

    /**
     * Check for unique of identifier of page to selected vendor.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    public function IsUniquePageToVendor(AbstractModel $object)
    {
        $errors = [];
        $select = $this->getConnection()->select()
            ->from(['cp' => $this->getMainTable()])
            ->where('cp.identifier = ?', $object->getData('identifier'))
            ->where('cp.vendor_id = ?', $object->getData('vendor_id'));

        if ($object->getId()) {
            $select->where('cp.page_id <> ?', $object->getId());
        }

        if ($this->getConnection()->fetchOne($select)) {
            $errors[] = __('The page identifier must be changed.It can\'t same with others page identifier\'s.');
            return $errors;
        }

        $transport = new \Magento\Framework\DataObject(
            [
                'errors' => $errors,
                'url_key' => $object->getIdentifier(),
                'vendor_id' => $object->getVendorId(),
                'type'=> 'cms'
            ]
        );

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $eventManager = $om->get(\Magento\Framework\Event\ManagerInterface::class);
        $eventManager->dispatch('page_url_key_validate', ['transport' => $transport]);
        $errors = $transport->getErrors();

        if (count($errors)) {
            return $errors;
        }

        return false;
    }


    /**
     * check url key exist
     * @param $vendorId
     * @param $urlKey
     * @return bool
     */
    public function loadByUrlKeyAndVendorId($vendorId , $urlKey){
        $table = $this->getMainTable();
        $connection = $this->getConnection();
        $select = $connection->select();

        $select->from(
            $table
        )->where(
            'identifier = :url_key'
        )->where(
            'vendor_id = :vendor_id'
        );

        $bind = [
            'url_key' => $urlKey,
            'vendor_id' => $vendorId
        ];

        $existRowId = $connection->fetchOne($select,$bind);

        if ($existRowId) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Framework\DataObject $object
     */
    public function afterDelete(\Magento\Framework\DataObject $object)
    {
        if ($object->isDeleted()) {
            $this->urlPersist->deleteByData(
                [
                    UrlRewrite::ENTITY_ID => $object->getId(),
                    UrlRewrite::ENTITY_TYPE => CmsPageUrlRewriteGenerator::ENTITY_TYPE,
                ]
            );
        }

        parent::afterDelete($object); // TODO: Change the autogenerated stub
    }

    /**
     * @param AbstractModel $object
     * @param string        $value
     * @param string|null   $field
     *
     * @return bool|int|string
     *
     * @throws LocalizedException
     * @throws \Exception
     */
    private function getPageId(AbstractModel $object, $value, $field = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        if (!is_numeric($value) && $field === null) {
            $field = 'identifier';
        } elseif (!$field) {
            $field = $entityMetadata->getIdentifierField();
        }
        $pageId = $value;
        if ($field != $entityMetadata->getIdentifierField()) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $select->reset(Select::COLUMNS)
                ->columns($this->getMainTable().'.'.$entityMetadata->getIdentifierField())
                ->where('vendor_id = ?', $object->getVendorId())
                ->limit(1);
            $result = $this->getConnection()->fetchCol($select);
            $pageId = count($result) ? $result[0] : false;
        }

        return $pageId;
    }

    /**
     * Load an object.
     *
     * @param CmsPage|AbstractModel $object
     * @param mixed                 $value
     * @param string                $field  field to load by (defaults to model id)
     *
     * @return $this
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $pageId = $this->getPageId($object, $value, $field);
        if ($pageId) {
            $this->entityManager->load($object, $pageId);
        }

        return $this;
    }

    /**
     * Retrieve select object for load object data.
     *
     * @param string                $field
     * @param mixed                 $value
     * @param CmsPage|AbstractModel $object
     *
     * @return Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
        $linkField = $entityMetadata->getLinkField();

        $select = parent::_getLoadSelect($field, $value, $object);

        return $select;
    }

    /**
     * Retrieve load select with filter by identifier, store and activity.
     *
     * @param string                                 $identifier
     * @param int|\Vnecoms\Vendors\Model\Vendor|null $vendor
     * @param int                                    $isActive
     *
     * @return Select
     */
    protected function _getLoadByIdentifierSelect($identifier, $vendor = null, $isActive = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = $this->getConnection()->select()
            ->from(['cp' => $this->getMainTable()])
            ->where('cp.identifier = ?', $identifier);
        if ($isActive !== null) {
            $select->where('cp.is_active = ?', $isActive);
        }
        if ($vendor !== null) {
            if ($vendor instanceof \Vnecoms\Vendors\Model\Vendor) {
                $vendor = $vendor->getId();
            }
            $select->where('cp.vendor_id = ?', $vendor);
        }

        return $select;
    }

    /**
     *  Check whether page identifier is numeric.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    protected function isNumericPageIdentifier(AbstractModel $object)
    {
        return preg_match('/^[0-9]+$/', $object->getData('identifier'));
    }

    /**
     *  Check whether page identifier is valid.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    protected function isValidPageIdentifier(AbstractModel $object)
    {
        return preg_match('/^[a-z0-9][a-z0-9_\/-]+(\.[a-z0-9_-]+)?$/', $object->getData('identifier'));
    }

    /**
     * Check if page identifier exist for specific vendor id
     * return page id if page exists.
     *
     * @param string $identifier
     * @param int    $vendorId
     *
     * @return int
     */
    public function checkIdentifier($identifier, $vendorId)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = $this->_getLoadByIdentifierSelect($identifier, $vendorId, 1);
        $select->reset(Select::COLUMNS)
            ->columns('cp.'.$entityMetadata->getIdentifierField())
            ->limit(1);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * Retrieves cms page title from DB by passed identifier.
     *
     * @param string   $identifier
     * @param int|null $vendorId
     *
     * @return string|false
     */
    public function getCmsPageTitleByIdentifier($identifier, $vendorId = null)
    {
        $select = $this->_getLoadByIdentifierSelect($identifier, $vendorId);
        $select->reset(Select::COLUMNS)
            ->columns('cp.title')
            ->order('cps.store_id DESC')
            ->limit(1);

        return $this->getConnection()->fetchOne($select);
    }

    /**
     * @param $id
     * @param null $vendor
     * @return string
     * @throws LocalizedException
     */
    public function getCmsPageTitleById($id, $vendor = null)
    {
        $connection = $this->getConnection();
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = $connection->select()
            ->from($this->getMainTable(), 'title')
            ->where($entityMetadata->getIdentifierField().' = :page_id')
            ->where('vendor_id = :vendor_id');
        if (!$vendor) {
            $vendorId = 0;
        } else {
            $vendorId = $vendor->getId();
        }

        return $connection->fetchOne($select, ['page_id' => (int) $id, 'vendor_id' => (int) $vendorId]);
    }

    /**
     * @param $id
     * @param null $vendor
     * @return string
     * @throws LocalizedException
     */
    public function getCmsPageIdentifierById($id, $vendor = null)
    {
        $connection = $this->getConnection();
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = $connection->select()
            ->from($this->getMainTable(), 'identifier')
            ->where($entityMetadata->getIdentifierField().' = :page_id')
            ->where('vendor_id = :vendor_id');

        if (!$vendor) {
            $vendorId = 0;
        } else {
            $vendorId = $vendor->getId();
        }

        return $connection->fetchOne($select, ['page_id' => (int) $id, 'vendor_id' => (int) $vendorId]);
    }

    /**
     * {@inheritdoc}
     */
//    public function save(AbstractModel $object)
//    {
//        $this->entityManager->save($object);
//        return $this;
//    }

    /**
     * {@inheritdoc}
     */
//    public function delete(AbstractModel $object)
//    {
//        $this->entityManager->delete($object);
//        return $this;
//    }

    /**
     * check that CMS Page owned by that owner or NOT.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    public function isValidOwner(AbstractModel $object)
    {
        return $object->getVendorId() == $this->getCurrentVendor()->getId();
    }
}
