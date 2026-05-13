<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Magento\Backend\Model\Url;

class UrlManagement extends Url
{
    /**
     * @inheritdoc
     */
    public function getUrl($routePath = null, $routeParams = null)
    {
        $this->getRouteParamsResolver()->unsetData('route_params');

        return parent::getUrl($routePath, $routeParams);
    }
}
