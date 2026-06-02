<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Raised for any Odoo external-API transport or protocol failure.
 */
class OdooException extends LocalizedException
{
}
