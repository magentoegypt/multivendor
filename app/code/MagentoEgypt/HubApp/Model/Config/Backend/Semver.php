<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Stores > Configuration > Hub Market App > App Versions: the minimum and latest version fields.
 *
 * The app compares its own version with these as major.minor.patch and skips the check when it
 * cannot read a value, so a mistyped minimum would silently stop a forced update. Saving refuses
 * anything but three whole numbers separated by dots (at most nine digits each, so every part fits
 * the app's integers) with a message naming the field; the section is then not saved. Surrounding
 * spaces are trimmed; empty is allowed and means no version policy.
 */
class Semver extends Value
{
    public const PATTERN = '/^\d{1,9}\.\d{1,9}\.\d{1,9}$/';

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = trim((string) $this->getValue());
        if ($value !== '' && !self::isValid($value)) {
            throw new LocalizedException(__(
                '%1: "%2" is not a valid version. Use major.minor.patch, three numbers separated by dots, for example 1.4.0.',
                $this->fieldLabel(),
                $value
            ));
        }
        $this->setValue($value);

        return parent::beforeSave();
    }

    public static function isValid(string $version): bool
    {
        return preg_match(self::PATTERN, $version) === 1;
    }

    /**
     * The field's label from system.xml (Config::save() passes it as field_config), else its path.
     */
    private function fieldLabel(): string
    {
        $config = $this->getData('field_config');
        $label = is_array($config) ? trim((string) ($config['label'] ?? '')) : '';

        return $label !== '' ? $label : (string) $this->getPath();
    }
}
