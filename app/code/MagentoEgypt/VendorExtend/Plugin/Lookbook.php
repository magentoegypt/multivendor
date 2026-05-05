<?php 
namespace MagentoEgypt\VendorExtend\Plugin;

class Lookbook
{
	public function afterGetData($subject, $pins, $key)
	{
		if($key == 'pins') $pins = $pins ?? '[]';
		return $pins;
	}
}