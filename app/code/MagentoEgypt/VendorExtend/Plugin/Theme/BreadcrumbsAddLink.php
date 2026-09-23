<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Theme;

use Magento\Theme\Block\Html\Breadcrumbs;

/**
 * Hub Market: accept the ADMIN breadcrumbs call on the storefront breadcrumbs block.
 *
 * Vnecoms wrote its seller controllers against the admin breadcrumbs widget
 * (Magento\Backend\Block\Widget\Breadcrumbs::addLink($label, $title, $url)). On the
 * storefront the `breadcrumbs` block is Magento\Theme\Block\Html\Breadcrumbs, which
 * only has addCrumb(); DataObject::__call rejects addLink and the seller "Add product"
 * page (Vnecoms\VendorsProduct\Controller\Catalog\Product\NewAction:99) died with
 *   LocalizedException: Invalid method ...Breadcrumbs\Interceptor::addLink
 * Vnecoms had already commented the identical block out of the storefront Edit
 * controller, but not out of NewAction.
 *
 * addLink is mapped onto addCrumb and returns the block, so the fluent
 * ->addLink()->addLink() chain keeps working. Every other magic call goes through
 * untouched. Magento core uses the same `__call` plugin convention
 * (ReCaptchaWebapiRest SoapValidationPlugin::before__call).
 */
class BreadcrumbsAddLink
{
    public function around__call(Breadcrumbs $subject, callable $proceed, $method, $args)
    {
        if ($method !== 'addLink') {
            return $proceed($method, $args);
        }
        $label = (string)($args[0] ?? '');
        $subject->addCrumb(
            'hm_' . md5($label),
            [
                'label' => $args[0] ?? '',
                'title' => $args[1] ?? ($args[0] ?? ''),
                'link'  => $args[2] ?? null,
            ]
        );
        return $subject;
    }
}
