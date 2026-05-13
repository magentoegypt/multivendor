<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Test\Unit\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Vnecoms\Quotation\Model\Item;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_ID = 1;
    const PRODUCT_TYPE = 'simple';
    const PRODUCT_SKU = '12345';
    const PRODUCT_NAME = 'test_product';
    const PRODUCT_WEIGHT = '1lb';
    const PRODUCT_TAX_CLASS_ID = 3;
    const PRODUCT_COST = '9.00';

    /**
     * @var \Vnecoms\Quotation\Model\Item
     */
    private $model;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Framework\Model\Context
     */
    private $modelContext;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventDispatcher;

    private $objectManagerHelper;



    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->localeFormat = $this->getMockBuilder('Magento\Framework\Locale\FormatInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->modelContext = $this->getMockBuilder('Magento\Framework\Model\Context')
            ->disableOriginalConstructor()
            ->setMethods(['getEventDispatcher'])
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Magento\Framework\Event\ManagerInterface')
            ->disableOriginalConstructor()
            ->setMethods(['dispatch'])
            ->getMock();

        $this->modelContext->expects($this->any())
            ->method('getEventDispatcher')
            ->will($this->returnValue($this->eventDispatcher));


        $this->model = $this->objectManagerHelper->getObject(
            '\Magento\Quote\Model\Quote\Item',
            [
                'localeFormat' => $this->localeFormat,
                'context' => $this->modelContext,
            ]
        );
    }

    private function generateProductMock(
        $productId,
        $productType,
        $productSku,
        $productName,
        $productWeight,
        $productTaxClassId,
        $productCost
    ) {
        $productMock = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId',
                    'getTypeId',
                    'getSku',
                    'getName',
                    'getWeight',
                    'getTaxClassId',
                    'getCost',
                    'setStoreId',
                    'setCustomerGroupId',
                    'getTypeInstance',
                    'getStickWithinParent',
                    'getCustomOptions',
                    'toArray',
                    '__wakeup',
                    'getStore',
                ]
            )
            ->getMock();

        $productMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($productId));
        $productMock->expects($this->any())
            ->method('getTypeId')
            ->will($this->returnValue($productType));
        $productMock->expects($this->any())
            ->method('getSku')
            ->will($this->returnValue($productSku));
        $productMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($productName));
        $productMock->expects($this->any())
            ->method('getWeight')
            ->will($this->returnValue($productWeight));
        $productMock->expects($this->any())
            ->method('getTaxClassId')
            ->will($this->returnValue($productTaxClassId));
        $productMock->expects($this->any())
            ->method('getCost')
            ->will($this->returnValue($productCost));
        $store = $this->getMock('Magento\Store\Model\Store', ['getWebsiteId'], [], '', false);
        $store->expects($this->any())
            ->method('getWebsiteId')
            ->will($this->returnValue(10));

        $productMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($store));

        return $productMock;
    }

    public function testSetProduct()
    {
        $productMock = $this->generateProductMock(
            self::PRODUCT_ID,
            self::PRODUCT_TYPE,
            self::PRODUCT_SKU,
            self::PRODUCT_NAME,
            self::PRODUCT_WEIGHT,
            self::PRODUCT_TAX_CLASS_ID,
            self::PRODUCT_COST
        );

        $this->model->setProduct($productMock);

        $this->assertEquals($productMock, $this->model->getProduct());
        $this->assertEquals(self::PRODUCT_ID, $this->model->getProductId());
        $this->assertEquals(self::PRODUCT_TYPE, $this->model->getData('product_type'));
        $this->assertEquals(self::PRODUCT_SKU, $this->model->getSku());
        $this->assertEquals(self::PRODUCT_NAME, $this->model->getName());
        $this->assertEquals(self::PRODUCT_COST, $this->model->getBaseCost());
        $this->assertNull($this->model->getIsQtyDecimal());
    }

    public function testRepresentProductTrue()
    {

    }

    public function testCompare()
    {

    }
}