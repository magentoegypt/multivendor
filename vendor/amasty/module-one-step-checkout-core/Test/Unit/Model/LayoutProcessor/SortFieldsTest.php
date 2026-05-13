<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\LayoutProcessor;

use Amasty\CheckoutCore\Model\LayoutProcessor\SortFields;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * @see SortFields
 * @covers SortFields::execute
 */
class SortFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $version
     * @param array $fields
     * @param array $expectedResult
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(string $version, array $fields, array $expectedResult): void
    {
        $productMetadataMock = $this->createConfiguredMock(
            ProductMetadataInterface::class,
            ['getVersion' => $version]
        );

        $subject = new SortFields($productMetadataMock);
        $subject->execute($fields);
        $this->assertSame(array_keys($expectedResult), array_keys($fields));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                '2.4.3',
                [
                    'custom_3' => ['sortOrder' => 0],
                    'lastname' => ['sortOrder' => 10],
                    'custom_5' => ['sortOrder' => 30],
                    'custom_1' => ['sortOrder' => 0],
                    'custom_4' => ['sortOrder' => 30],
                    'prefix' => ['sortOrder' => 20],
                    'firstname' => ['sortOrder' => 20],
                    'custom_2' => ['sortOrder' => 0],
                ],
                [
                    'custom_3' => ['sortOrder' => 0],
                    'custom_2' => ['sortOrder' => 0],
                    'custom_1' => ['sortOrder' => 0],
                    'lastname' => ['sortOrder' => 10],
                    'prefix' => ['sortOrder' => 20],
                    'firstname' => ['sortOrder' => 20],
                    'custom_5' => ['sortOrder' => 30],
                    'custom_4' => ['sortOrder' => 30]
                ]
            ],
            [
                '2.4.4',
                [
                    'custom_3' => ['sortOrder' => 0],
                    'lastname' => ['sortOrder' => 10],
                    'custom_5' => ['sortOrder' => 30],
                    'custom_1' => ['sortOrder' => 0],
                    'custom_4' => ['sortOrder' => 30],
                    'prefix' => ['sortOrder' => 20],
                    'firstname' => ['sortOrder' => 20],
                    'custom_2' => ['sortOrder' => 0],
                ],
                [
                    'custom_1' => ['sortOrder' => 0],
                    'custom_2' => ['sortOrder' => 0],
                    'custom_3' => ['sortOrder' => 0],
                    'lastname' => ['sortOrder' => 10],
                    'firstname' => ['sortOrder' => 20],
                    'prefix' => ['sortOrder' => 20],
                    'custom_4' => ['sortOrder' => 30],
                    'custom_5' => ['sortOrder' => 30]
                ]
            ]
        ];
    }
}
