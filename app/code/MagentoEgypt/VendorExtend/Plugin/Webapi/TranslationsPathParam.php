<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Webapi;

use Magento\Webapi\Controller\Rest\ParamsOverrider;
use MagentoEgypt\VendorExtend\Api\ProductTranslationsInterface;

/**
 * PUT /V1/vendors/product/:sku/translations answered 500 before reaching the service.
 *
 * On every PUT, core copies the URL's last parameter into the body's data object when the body has exactly
 * one key holding an array. This body is {"translations": [...]}, an ARRAY of data objects, so core reflects
 * the type "...StoreTranslationInterface[]" as a class and throws ReflectionException. For this service the
 * copy is not wanted at all: `sku` is its own method argument, and InputParamsResolver merges the URL
 * parameters into the input right after this call.
 */
class TranslationsPathParam
{
    public function aroundOverrideRequestBodyIdWithPathParam(
        ParamsOverrider $subject,
        callable $proceed,
        array $urlPathParams,
        array $requestBodyParams,
        $serviceClassName,
        $serviceMethodName
    ) {
        if (ltrim((string) $serviceClassName, '\\') === ProductTranslationsInterface::class) {
            return $requestBodyParams;
        }

        return $proceed($urlPathParams, $requestBodyParams, $serviceClassName, $serviceMethodName);
    }
}
