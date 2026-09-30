<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HubAppOrders\Model\Package\PackageService;
use MagentoEgypt\HubAppOrders\Model\Resolver\OrderPackages;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * CustomerOrder.hm_packages: every order of a response in one call, each order its own packages,
 * and nothing for a parent without the order model the core query put there.
 */
final class OrderPackagesTest extends TestCase
{
    private const STORE = 2;

    public function testEveryOrderOfTheResponseInOneCall(): void
    {
        $first = $this->order(10);
        $second = $this->order(11);
        $service = $this->createMock(PackageService::class);
        $service->expects(self::once())
            ->method('forOrders')
            ->with([$first, $second], self::STORE)
            ->willReturn([10 => [['status_code' => 'complete']], 11 => [['status_code' => 'pending']]]);

        $requests = ['a' => $this->request(['model' => $first]), 'b' => $this->request(['model' => $second])];
        $response = $this->resolver($service)->resolve($this->context(), $this->field(), $requests);

        self::assertSame([['status_code' => 'complete']], $response->findResponseFor($requests['a']));
        self::assertSame([['status_code' => 'pending']], $response->findResponseFor($requests['b']));
    }

    public function testAParentWithoutAnOrderModelGetsNoPackagesAndNoRead(): void
    {
        $service = $this->createMock(PackageService::class);
        $service->expects(self::never())->method('forOrders');

        $requests = [$this->request(['number' => '000000010']), $this->request(null)];
        $response = $this->resolver($service)->resolve($this->context(), $this->field(), $requests);

        self::assertSame([], $response->findResponseFor($requests[0]));
        self::assertSame([], $response->findResponseFor($requests[1]));
    }

    public function testAnOrderWithoutPackagesGetsAnEmptyList(): void
    {
        $service = $this->createMock(PackageService::class);
        $service->method('forOrders')->willReturn([]);

        $request = $this->request(['model' => $this->order(12)]);
        $response = $this->resolver($service)->resolve($this->context(), $this->field(), [$request]);

        self::assertSame([], $response->findResponseFor($request));
    }

    public function testAFailureIsLoggedAndLeavesTheOrdersWithoutPackages(): void
    {
        //  The field is non-null: an error would take the whole order out of the customer's list.
        $service = $this->createMock(PackageService::class);
        $service->method('forOrders')->willThrowException(new \RuntimeException('Table missing'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(self::stringContains('Table missing'));

        $request = $this->request(['model' => $this->order(10)]);
        $response = $this->resolver($service, $logger)->resolve($this->context(), $this->field(), [$request]);

        self::assertSame([], $response->findResponseFor($request));
    }

    private function resolver(PackageService $service, ?LoggerInterface $logger = null): OrderPackages
    {
        return new OrderPackages($service, $logger ?? $this->createMock(LoggerInterface::class));
    }

    /**
     * @param array<string, mixed>|null $value
     */
    private function request(?array $value): BatchRequestItemInterface
    {
        $request = $this->createMock(BatchRequestItemInterface::class);
        $request->method('getValue')->willReturn($value);

        return $request;
    }

    private function order(int $id): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getEntityId')->willReturn($id);

        return $order;
    }

    private function field(): Field
    {
        return $this->createMock(Field::class);
    }

    private function context(): ContextInterface
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE);

        //  The request context exposes the store through its (generated) extension attributes.
        return new class ($store) implements ContextInterface {
            public function __construct(private readonly StoreInterface $store)
            {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->store) {
                    public function __construct(private readonly StoreInterface $store)
                    {
                    }

                    public function getStore(): StoreInterface
                    {
                        return $this->store;
                    }
                };
            }
        };
    }
}
