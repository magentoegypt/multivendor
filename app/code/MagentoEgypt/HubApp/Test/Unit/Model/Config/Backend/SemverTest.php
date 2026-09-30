<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Config\Backend;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MagentoEgypt\HubApp\Model\Config\Backend\Semver;
use PHPUnit\Framework\TestCase;

/**
 * App Versions: only major.minor.patch is saved; anything else is refused with a message naming the field.
 */
final class SemverTest extends TestCase
{
    public function testOnlyThreeWholeNumbersAreVersions(): void
    {
        foreach (['1.4.0', '0.0.1', '10.20.300', '2.0.10', '999999999.0.0'] as $version) {
            self::assertTrue(Semver::isValid($version), $version);
        }
        foreach ([
            '1.4', '1', 'v1.4.0', '1.4.0-beta', '1.4.0+12', '1.4.0.1', '1..0', '.1.4', 'a.b.c',
            '1,4,0', '1.4.0 ', '١.٤.٠', '1234567890.0.0', '',
        ] as $version) {
            self::assertFalse(Semver::isValid($version), $version);
        }
    }

    public function testAnInvalidVersionIsRefusedNamingTheField(): void
    {
        $model = $this->model('1.4', ['id' => 'android_min', 'label' => 'Android Minimum Version']);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage(
            'Android Minimum Version: "1.4" is not a valid version. Use major.minor.patch, three numbers '
            . 'separated by dots, for example 1.4.0.'
        );
        $model->beforeSave();
    }

    public function testWithoutAFieldLabelThePathIsNamed(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('hubapp/version/android_min: "latest" is not a valid version.');
        $this->model('latest')->beforeSave();
    }

    public function testAValidVersionIsSavedTrimmedAndEmptyMeansNoPolicy(): void
    {
        self::assertSame('1.4.0', $this->model(' 1.4.0 ')->beforeSave()->getValue());
        self::assertSame('', $this->model('  ')->beforeSave()->getValue());
        self::assertSame('', $this->model(null)->beforeSave()->getValue());
    }

    /**
     * The backend model as Config::save() hands it a field: path, posted value and field_config.
     *
     * @param array<string, string>|null $fieldConfig
     */
    private function model(?string $value, ?array $fieldConfig = null): Semver
    {
        $model = $this->getMockBuilder(Semver::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $events = new \ReflectionProperty(AbstractModel::class, '_eventManager');
        $events->setAccessible(true);
        $events->setValue($model, $this->createMock(ManagerInterface::class));
        $model->setData(['path' => 'hubapp/version/android_min', 'value' => $value, 'field_config' => $fieldConfig]);

        return $model;
    }
}
