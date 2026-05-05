<?php 
namespace Accept\Payments\Model\Config\Source;

class Currency implements \Magento\Framework\Option\ArrayInterface
{
	public function toOptionArray()
    {
    	return [
    		['label' => 'Egyptian Pound', 'value' => 'EGP'],
    		['label' => 'Euro', 'value' => 'EUR'],
    		['label' => 'British Pound', 'value' => 'GBP'],
    		['label' => 'US Doller', 'value' => 'USD']
    	];
    }
}