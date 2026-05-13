<?php

namespace Vnecoms\Quotation\Plugin\Admin;

class AfterRenderConfigureResult
{
    protected $request;

    public function __construct
    (
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Remove qty fields
     * @param \Magento\Catalog\Helper\Product\Composite $composite
     * @param $result
     * @return mixed
     */
    public function afterRenderConfigureResult(\Magento\Catalog\Helper\Product\Composite $composite, $result)
    {
        $module = $this->getRequest()->getModuleName();
        $action = $this->getRequest()->getActionName();
        $controllerName = $this->getRequest()->getControllerName();

        if ($module == 'quotation') {
            return $result->addHandle('quotation_quote_create_configure_quote');
        }
        else return $result;
    }
}