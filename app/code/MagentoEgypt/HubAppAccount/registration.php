<?php
/**
 * MagentoEgypt_HubAppAccount — account features of the Hub Market customer app over GraphQL.
 *
 * A satellite of MagentoEgypt_HubApp: store credit (Vnecoms Credit: balance, transactions, using it on
 * the cart, the credit on orders), push-notification devices (MagentoEgypt_PushNotification's device
 * table) and signing in with a WhatsApp code (MagentoEgypt_SmsExtend's OTP).
 *
 * Disabling this module removes its GraphQL fields and nothing else. No setup_version: its columns
 * come from db_schema.xml.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'MagentoEgypt_HubAppAccount', __DIR__);
