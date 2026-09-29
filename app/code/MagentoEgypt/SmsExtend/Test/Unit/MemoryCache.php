<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit;

use Magento\Framework\App\CacheInterface;

/**
 * In-memory CacheInterface for the throttle and OTP guard tests (lifetimes are not enforced: the classes
 * under test keep their own time).
 */
class MemoryCache implements CacheInterface
{
    /** @var array<string, string> */
    public array $entries = [];

    /** @var array<string, string[]> */
    public array $tags = [];

    public function getFrontend()
    {
        return null;
    }

    public function load($identifier)
    {
        return $this->entries[$identifier] ?? false;
    }

    public function save($data, $identifier, $tags = [], $lifeTime = null)
    {
        $this->entries[$identifier] = (string) $data;
        $this->tags[$identifier] = $tags;

        return true;
    }

    public function remove($identifier)
    {
        unset($this->entries[$identifier], $this->tags[$identifier]);

        return true;
    }

    public function clean($tags = [])
    {
        $this->entries = [];
        $this->tags = [];

        return true;
    }
}
