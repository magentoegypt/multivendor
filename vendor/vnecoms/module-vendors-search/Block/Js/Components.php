<?php

namespace Vnecoms\VendorsSearch\Block\Js;

use Magento\Framework\View\Element\Template;

class Components extends \Magento\Framework\View\Element\Template
{
    protected $searchHelper;

    public function __construct(Template\Context $context, \Vnecoms\VendorsSearch\Helper\Data $searchHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->searchHelper = $searchHelper;
    }

    public function getComponentsJson()
    {
        $components = [];
        $components['components'] = [
            'autocompleteInjection' => [
                'component' => 'Vnecoms_VendorsSearch/js/injection',
                'config' => [],
            ],
            'autocomplete' => [
                'component' => 'Vnecoms_VendorsSearch/js/autocomplete',
                'provider' => 'autocompleteProvider',
                'config' => [
                    'priceFormat' => [
                        'precision' => 2,

                    ]
                ]
            ],
            'autocompleteProvider' => [
                'component' => 'Vnecoms_VendorsSearch/js/provider',
                'config' => [
                    'minSearchLength'=> '3',
                    'delay' => '300',
                    'url' => $this->getAjaxSuggestUrl()
                ]
            ],
            'autocompleteNavigation' => [
                'component' => 'Vnecoms_VendorsSearch/js/navigation',
                'autocomplete' => 'autocomplete'
            ]
        ];
        return \Laminas\Json\Json::encode($components);
    }

    public function getAjaxSuggestUrl()
    {
        return $this->searchHelper->getSuggestUrl();
    }
}
