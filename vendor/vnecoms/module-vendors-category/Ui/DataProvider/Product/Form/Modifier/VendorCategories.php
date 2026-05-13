<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Data provider for categories field of product page.
 */
class VendorCategories extends AbstractModifier
{
    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var DbHelper
     */
    protected $dbHelper;

    /**
     * @var array
     */
    protected $categoriesTrees = [];

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param DbHelper                  $dbHelper
     * @param UrlInterface              $urlBuilder
     * @param ArrayManager              $arrayManager
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        DbHelper $dbHelper,
        UrlInterface $urlBuilder,
        ArrayManager $arrayManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->dbHelper = $dbHelper;
        $this->urlBuilder = $urlBuilder;
        $this->arrayManager = $arrayManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->createNewCategoryModal($meta);
        $meta = $this->customizeCategoriesField($meta);

       // echo "<pre>";var_dump($meta);die();
        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Create slide-out panel for new category creation.
     *
     * @param array $meta
     *
     * @return array
     */
    protected function createNewCategoryModal(array $meta)
    {
        return $this->arrayManager->set(
            'ves_create_category_modal',
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'isTemplate' => false,
                            'componentType' => 'modal',
                            'options' => [
                                'title' => __('New Vendor Category'),
                            ],
                            'imports' => [
                                'state' => '!index=ves_create_category:responseStatus',
                            ],
                        ],
                    ],
                ],
                'children' => [
                    'ves_create_category' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => '',
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/insert-form',
                                    'dataScope' => '',
                                    'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                    'render_url' => $this->urlBuilder->getUrl(
                                        'mui/index/render_handle',
                                        [
                                            'handle' => 'vendor_catalog_category_create',
                                            'store' => 1,
                                            'buttons' => 1,
                                        ]
                                    ),
                                    'autoRender' => false,
                                    'ns' => 'vendor_new_category_form',
                                    'externalProvider' => 'vendor_new_category_form.vendor_new_category_form_data_source',
                                    'toolbarContainer' => '${ $.parentName }',
                                    'formSubmitType' => 'ajax',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Customize Categories field.
     *
     * @param array $meta
     *
     * @return array
     */
    protected function customizeCategoriesField(array $meta)
    {
        $categoryField = 'category_ids';
        $categoryElementPath = $this->arrayManager->findPath($categoryField, $meta, null, 'children');
        $categoryContainerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX.$categoryField, $meta, null, 'children');

        //var_dump($categoryElementPath);
        $categorySortOrder = $this->arrayManager->get($categoryContainerPath.'/arguments/data/config/sortOrder', $meta);
        $fieldCode = 'ves_category_ids';
        $elementPath = $this->arrayManager->findPath($fieldCode, $meta, null, 'children');
        $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX.$fieldCode, $meta, null, 'children');

        //check exist element path
        if (!$elementPath) {
            return $meta;
        }

        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Vendor Categories'),
                            'dataScope' => '',
                            'breakLine' => false,
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'Magento_Ui/js/form/components/group',
                            'sortOrder' => $categorySortOrder,
                            'additionalClasses' => 'admin__field-clean',
                           // 'scopeLabel' => __('[GLOBAL]'),
                        ],
                    ],
                ],
                'children' => [
                    $fieldCode => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'formElement' => 'select',
                                    'componentType' => 'field',
                                    'component' => 'Vnecoms_VendorsCategory/js/components/new-category',
                                    'filterOptions' => true,
                                    'chipsEnabled' => true,
                                    'disableLabel' => true,
                                    'levelsVisibility' => '2',
                                    'elementTmpl' => 'Vnecoms_VendorsCategory/ui-select',
                                 //   'elementTmpl' => 'ui/grid/filters/elements/ui-select',
                                    'options' => $this->getCategoriesTree(),
                                    'listens' => [
                                        'index=ves_create_category:responseData' => 'setParsed',
                                        'newOption' => 'toggleOptionSelected',
                                    ],
                                    'config' => [
                                        'dataScope' => $fieldCode,
                                        'sortOrder' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'ves_create_category_button' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'title' => __('New Vendor Category'),
                                    'formElement' => 'container',
                                    'additionalClasses' => 'admin__field-small',
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/button',
                                    'template' => 'ui/form/components/button/container',
                                    'actions' => [
                                        [
                                            'targetName' => 'product_form.product_form.ves_create_category_modal',
                                            'actionName' => 'toggleModal',
                                        ],
                                        [
                                            'targetName' => 'product_form.product_form.ves_create_category_modal.ves_create_category',
                                            'actionName' => 'render',
                                        ],
                                        [
                                            'targetName' => 'product_form.product_form.ves_create_category_modal.ves_create_category',
                                            'actionName' => 'resetForm',
                                        ],
                                    ],
                                    'additionalForGroup' => true,
                                    'provider' => false,
                                    'source' => 'product_details',
                                    'displayArea' => 'insideGroup',
                                    'sortOrder' => 20,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * Retrieve categories tree.
     *
     * @param string|null $filter
     *
     * @return array
     */
    protected function getCategoriesTree($filter = null)
    {
        if (isset($this->categoriesTrees[$filter])) {
            return $this->categoriesTrees[$filter];
        }

        /* @var $matchingNamesCollection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
        $matchingNamesCollection = $this->categoryCollectionFactory->create();

        if ($filter !== null) {
            $matchingNamesCollection->addFieldToFilter(
                'name',
                ['like' => $this->dbHelper->addLikeEscape($filter, ['position' => 'any'])]
            );
        }

        $matchingNamesCollection
            ->addFieldToFilter('category_id', ['neq' => $this->getRootId()])
            ->addFieldToFilter('vendor_id', $this->getVendor()->getId());

        $shownCategoriesIds = [];

        /** @var \Vnecoms\VendorsCategory\Model\Category $category */
        foreach ($matchingNamesCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }
        /* @var $collection \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addFieldToFilter('category_id', ['in' => array_keys($shownCategoriesIds)]);

        $categoryById = [
            $this->getRootId() => [
                'value' => $this->getRootId(),
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getId()]['parent'] = $category->getParentId();
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        $this->categoriesTrees[$filter] = $categoryById[$this->getRootId()]['optgroup'];

      //  echo "<pre>";var_dump($categoryById);die();
        return $this->categoriesTrees[$filter];
    }

    /**
     * get Root ID.
     *
     * @return int
     */
    public function getRootId()
    {
        return 0;
        $category = \Magento\Framework\App\ObjectManager::getInstance()->create(
            'Vnecoms\VendorsCategory\Model\Category'
        )->getRootId($this->getVendor()->getId());

        return $category;
    }

    public function getRootVendorId()
    {
        $category = \Magento\Framework\App\ObjectManager::getInstance()->create(
            'Vnecoms\VendorsCategory\Model\Category'
        )->getRootId($this->getVendor()->getId());

        return $category;
    }

    public function getVendor()
    {
        if ($this->vendorSession) {
            return $this->vendorSession->getVendor();
        } else {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }
}
