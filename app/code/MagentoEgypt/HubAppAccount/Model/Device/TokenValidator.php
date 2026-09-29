<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Device;

/**
 * Checks and normalises hmRegisterDevice / hmUnregisterDevice input before anything touches the table.
 *
 *  - token: an FCM registration token, 20-500 characters of [A-Za-z0-9_:-.] (design 3; the column is
 *    varchar(500) with a unique index);
 *  - platform: HmPlatform to the table's values (android / ios);
 *  - app_version: optional, at most 32 characters starting with a letter or digit, of letters, digits,
 *    ". + - _ ( )" and spaces (so "1.4.0+37" and "1.4.0 (37)" both pass); blank means none.
 */
final class TokenValidator
{
    private const TOKEN = '/^[A-Za-z0-9_:\-.]{20,500}$/';
    private const VERSION = '/^[0-9A-Za-z][0-9A-Za-z.+\-_ ()]{0,31}$/';
    private const PLATFORMS = ['ANDROID' => 'android', 'IOS' => 'ios'];

    public function isValidToken(string $token): bool
    {
        return preg_match(self::TOKEN, $token) === 1;
    }

    /**
     * The table's platform value, or null for anything but ANDROID / IOS.
     */
    public function platform(string $platform): ?string
    {
        return self::PLATFORMS[strtoupper(trim($platform))] ?? null;
    }

    /**
     * @param array<string, mixed> $input HmRegisterDeviceInput
     * @return array{token: string, platform: string, app_version: ?string}|null null when invalid
     */
    public function registration(array $input): ?array
    {
        $token = $this->token($input['token'] ?? null);
        $platform = is_string($input['platform'] ?? null) ? $this->platform($input['platform']) : null;
        $version = $input['app_version'] ?? null;
        if ($token === null || $platform === null || ($version !== null && !is_string($version))) {
            return null;
        }
        $version = trim((string) $version);
        if ($version !== '' && preg_match(self::VERSION, $version) !== 1) {
            return null;
        }

        return ['token' => $token, 'platform' => $platform, 'app_version' => $version !== '' ? $version : null];
    }

    /**
     * @param array<string, mixed> $input HmUnregisterDeviceInput
     * @return string|null the token, or null when invalid
     */
    public function unregistration(array $input): ?string
    {
        return $this->token($input['token'] ?? null);
    }

    private function token(mixed $token): ?string
    {
        if (!is_string($token)) {
            return null;
        }
        $token = trim($token);

        return $this->isValidToken($token) ? $token : null;
    }
}
