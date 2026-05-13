<?php

namespace Vnecoms\Quotation\Helper;

use Magento\Framework\App\Helper\Context;
use Vnecoms\Quotation\Model\Item;

class Url extends \Magento\Framework\Url\Helper\Data
{
    const DELETE_URL = 'quotation/quote/delete';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function getDeletePostJson(Item $item)
    {
        $url = $this->_getUrl(self::DELETE_URL);

        $data = ['id' => $item->getId()];
        if (!$this->_request->isAjax()) {
            $data[\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED] = $this->getCurrentBase64Url();
        }
        return json_encode(['action' => $url, 'data' => $data]);
    }

    public function getRemoveUrl(Item $item)
    {
        $params = [
            'id' => $item->getId(),
            \Magento\Framework\App\ActionInterface::PARAM_NAME_BASE64_URL => $this->getCurrentBase64Url(),
        ];
        return $this->_getUrl(self::DELETE_URL, $params);
    }

    /**
     * Retrieve url for add product to quote
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional
     * @return  string
     */
    public function getAddUrl($product, $additional = [])
    {
        $continueUrl = $this->urlEncoder->encode($this->_urlBuilder->getCurrentUrl());
        $urlParamName = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;

        $routeParams = [
            $urlParamName => $continueUrl,
            'product' => $product->getEntityId(),
            '_secure' => $this->_getRequest()->isSecure()
        ];

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_scope_to_url'] = true;
        }

        if ($this->_getRequest()->getRouteName() == 'quotation'
            && $this->_getRequest()->getControllerName() == 'quote'
        ) {
            $routeParams['in_quote'] = 1;
        }

        return $this->_getUrl('quotation/quote/add', $routeParams);
    }
}