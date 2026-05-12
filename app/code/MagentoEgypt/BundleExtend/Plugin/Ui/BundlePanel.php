<?php
namespace MagentoEgypt\BundleExtend\Plugin\Ui;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use MagentoEgypt\BundleExtend\Helper\Data as BundleExtendHelper;

class BundlePanel
{
    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    public function __construct(LocatorInterface $locator, ArrayManager $arrayManager)
    {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
    }

    public function beforeModifyMeta(
        \Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel $subject,
        array $meta
    ) {
        if ($this->arrayManager->findPath('shipment_type', $meta, null, 'children') === null) {
            $meta['shipment_type'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => 'field',
                            'visible' => false,
                        ],
                    ],
                ],
            ];
        }
        return [$meta];
    }

    public function afterModifyMeta(
        \Magento\Bundle\Ui\DataProvider\Product\Form\Modifier\BundlePanel $subject,
        $meta
    ) {
        if ($this->locator->getProduct()->getTypeId() !== BundleExtendHelper::NEW_BUNDLE_TYPE_CODE) {
            return $meta;
        }

        $path = [
            'bundle-items',
            'children',
            'bundle_options',
            'children',
            'record',
            'children',
            'product_bundle_container',
            'children',
            'option_info',
            'children'
        ];

        $container = &$meta;

        foreach ($path as $key) {
            if (!isset($container[$key])) {
                return $meta;
            }
            $container = &$container[$key];
        }

        /**
         * Discount Type
         */
        $container['discount_type'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Discount Type'),
                        'componentType' => 'field',
                        'formElement' => 'select',
                        'dataScope' => 'discount_type',
                        'dataType' => 'text',
                        'sortOrder' => 21,
                        'options' => [
                            ['label' => __('Fixed'), 'value' => 'fixed'],
                            ['label' => __('Percentage'), 'value' => 'percent'],
                        ],
                    ],
                ],
            ],
        ];

        /**
         * Discount Amount
         */
        $container['discount_amount'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Discount Amount'),
                        'componentType' => 'field',
                        'formElement' => 'input',
                        'dataScope' => 'discount_amount',
                        'dataType' => 'number',
                        'sortOrder' => 22,
                        'validation' => [
                            'validate-number' => true,
                            'validate-zero-or-greater' => true
                        ],
                    ],
                ],
            ],
        ];

        return $meta;
    }
}
