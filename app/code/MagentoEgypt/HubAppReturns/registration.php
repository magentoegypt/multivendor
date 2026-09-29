<?php
/**
 * MagentoEgypt_HubAppReturns — returns (Vnecoms RMA) for the Hub Market customer app over GraphQL.
 *
 * A satellite of MagentoEgypt_HubApp: the return form settings, the customer's returnable orders,
 * their returns with history and messages, filing a return and replying to one. The rules are the
 * website's (Vnecoms_RMA + Vnecoms_VendorsRMA as Hub Market runs them), plus the two checks the
 * website's save controller lacks: the order must be the signed-in customer's, and every line must
 * belong to that order.
 *
 * Disabling this module removes its GraphQL fields and nothing else; no other module depends on it.
 * No setup_version (no tables of its own).
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubAppReturns', __DIR__);
