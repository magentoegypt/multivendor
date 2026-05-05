<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Block;

class ImportDemoButton
{
	protected $request;

	public function __construct(
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->request = $request;
    }

	public function afterGetData($subject, $pins, $key)
	{
		if($key == 'ajax_url') {
			$id = $this->request->getParam('id', false);
			if($id>0) {
				$pins .= 'theme/'.$id.'/';
			}
		}
		return $pins;
	}
}