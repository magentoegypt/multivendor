<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\Form\SortFields;

/**
 * @see SortFields
 * @covers SortFields::execute
 */
class SortFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $fields
     * @param array $expectedResult
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $fields, array $expectedResult): void
    {
        $subject = new SortFields();
        $subject->execute($fields);
        $this->assertSame(array_keys($expectedResult), array_keys($fields));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                [
                    'custom_3' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                    'lastname' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 10]),
                    'custom_5' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 30]),
                    'custom_1' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                    'custom_4' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 30]),
                    'prefix' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 20]),
                    'firstname' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 20]),
                    'custom_2' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                ],
                [
                    'custom_1' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                    'custom_2' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                    'custom_3' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 0]),
                    'lastname' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 10]),
                    'firstname' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 20]),
                    'prefix' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 20]),
                    'custom_4' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 30]),
                    'custom_5' => $this->createConfiguredMock(Field::class, ['getSortOrder' => 30])
                ]
            ]
        ];
    }
}
