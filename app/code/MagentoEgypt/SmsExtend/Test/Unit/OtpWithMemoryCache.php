<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit;

use MagentoEgypt\SmsExtend\Helper\Otp;

/**
 * The real OTP helper (codes, attempt counter, cache keys) on an in-memory cache; its constructor needs
 * the whole application.
 */
class OtpWithMemoryCache extends Otp
{
    public function __construct()
    {
        $this->cache = new MemoryCacheFrontend();
    }
}
