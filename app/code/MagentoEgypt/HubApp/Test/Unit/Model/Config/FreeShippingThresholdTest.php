<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config;

use Magento\Framework\DataObject;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Model\Config\FreeShippingThreshold;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MagentoEgypt\HubApp\Model\Config\FreeShippingThreshold
 */
class FreeShippingThresholdTest extends TestCase
{
    public function testTheLowestSubtotalPromiseOfTheCouponFreeRules(): void
    {
        $rules = [
            //  "Spend 50 or more - shipping is free!", the live rule.
            self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal', '>=', '50']]),
            //  A lower one behind a coupon: not a promise the cart makes unprompted.
            self::rule(Rule::COUPON_TYPE_SPECIFIC, [['base_subtotal', '>=', '20']]),
            self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal', '>', '75'], ['total_qty', '>=', '1']]),
        ];

        self::assertSame(50.0, FreeShippingThreshold::lowest($rules));
    }

    public function testOnlySubtotalFloorsCount(): void
    {
        self::assertNull(FreeShippingThreshold::lowest([]));
        self::assertNull(FreeShippingThreshold::lowest([
            //  No conditions (the expired "Free Shipping Offer"): the storefront names no threshold.
            self::rule(Rule::COUPON_TYPE_NO_COUPON, []),
            self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal', '<', '50']]),
            self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal_with_discount', '>=', '10']]),
            self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal', '>=', 'fifty']]),
        ]));
        self::assertSame(0.0, FreeShippingThreshold::lowest([self::rule(Rule::COUPON_TYPE_NO_COUPON, [['base_subtotal', '>=', '0']])]));
    }

    public function testRulesAreAskedForTheWebsiteAndGroupWithFreeShipping(): void
    {
        $combine = new DataObject(['conditions' => [
            new DataObject(['attribute' => 'base_subtotal', 'operator' => '>=', 'value' => '50']),
        ]]);
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConditions', 'getId'])
            ->addMethods(['getCouponType'])
            ->getMock();
        $rule->method('getConditions')->willReturn($combine);
        $rule->method('getCouponType')->willReturn((string) Rule::COUPON_TYPE_NO_COUPON);

        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())->method('setValidationFilter')->with(3, 1)->willReturnSelf();
        $collection->expects(self::once())->method('addFieldToFilter')
            ->with('simple_free_shipping', ['gt' => 0])->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$rule]));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(3);
        $stores = $this->createMock(StoreManagerInterface::class);
        $stores->method('getStore')->with(2)->willReturn($store);

        $threshold = new FreeShippingThreshold($factory, $stores, $this->createMock(LoggerInterface::class));

        self::assertSame(50.0, $threshold->forStore(2, 1));
    }

    public function testAnUnreadableRuleSetIsNoThreshold(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('no table'));
        $stores = $this->createMock(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($this->createMock(StoreInterface::class));

        $threshold = new FreeShippingThreshold($factory, $stores, $this->createMock(LoggerInterface::class));

        self::assertNull($threshold->forStore(1, 0));
    }

    /**
     * @param array<int, array{0: string, 1: string, 2: mixed}> $conditions
     * @return array{coupon_type: int, conditions: array<int, array{attribute: string, operator: string, value: mixed}>}
     */
    private static function rule(int $couponType, array $conditions): array
    {
        return [
            'coupon_type' => $couponType,
            'conditions' => array_map(
                static fn (array $c): array => ['attribute' => $c[0], 'operator' => $c[1], 'value' => $c[2]],
                $conditions
            ),
        ];
    }
}
