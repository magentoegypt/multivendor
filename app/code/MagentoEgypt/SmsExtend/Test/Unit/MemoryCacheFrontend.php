<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit;

use Magento\Framework\Cache\FrontendInterface;

/**
 * In-memory cache frontend: the one the OTP helper keeps its codes in (the "default" cache).
 */
class MemoryCacheFrontend implements FrontendInterface
{
    /** @var array<string, string> */
    public array $entries = [];

    public function test($identifier)
    {
        return isset($this->entries[$identifier]);
    }

    public function load($identifier)
    {
        return $this->entries[$identifier] ?? false;
    }

    public function save($data, $identifier, array $tags = [], $lifeTime = null)
    {
        $this->entries[$identifier] = (string) $data;

        return true;
    }

    public function remove($identifier)
    {
        unset($this->entries[$identifier]);

        return true;
    }

    public function clean($mode = 'all', array $tags = [])
    {
        $this->entries = [];

        return true;
    }

    public function getBackend()
    {
        return null;
    }

    public function getLowLevelFrontend()
    {
        return null;
    }
}
