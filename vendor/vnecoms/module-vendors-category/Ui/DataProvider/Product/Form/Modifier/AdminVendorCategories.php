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
class AdminVendorCategories extends AbstractModifier
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
        $meta = $this->hideCategoriesField($meta);
       // $meta = $this->customizeCategoriesField($meta);

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    public function hideCategoriesField(array $meta)
    {
        $fieldCode = 'ves_category_ids';
        $elementPath = $this->arrayManager->findPath($fieldCode, $meta, null, 'children');
        $containerPath = $this->arrayManager->findPath(static::CONTAINER_PREFIX.$fieldCode, $meta, null, 'children');

        //check exist element path
        if (!$elementPath) {
            return $meta;
        }

        $meta = $this->arrayManager->merge(
            $elementPath,
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => '',
                            'dataScope' => $fieldCode,
                            'breakLine' => false,
                            'formElement' => 'hidden',
                            'componentType' => 'field',
                        ],
                    ],
                ],
            ]
        );

        return $meta;
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
                                    'levelsVisibility' => '1',
                                    'elementTmpl' => 'ui/grid/filters/elements/ui-select',
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
            ->addFieldToFilter('category_id', ['neq' => 0]);

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
            0 => [
                'value' => 0,
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
        $category = \Magento\Framework\App\ObjectManager::getInstance()->create(
            'Vnecoms\VendorsCategory\Model\Category'
        )->getRootId($this->getVendor()->getId());

        return $category;
    }

    public function getVendor()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\Vendors\Model\Vendor')->load(2);
    }
}
