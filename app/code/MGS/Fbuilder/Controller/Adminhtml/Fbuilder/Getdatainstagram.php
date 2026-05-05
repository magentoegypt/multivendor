<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MGS\Fbuilder\Controller\Adminhtml\Fbuilder;

class Getdatainstagram extends \MGS\Fbuilder\Controller\Adminhtml\Fbuilder
{
	/**
	* @var \Magento\Framework\Controller\Result\JsonFactory
	*/
	protected $resultJsonFactory;
	
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	)
    {
        parent::__construct($context);
		$this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Edit sitemap
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute() {
		$result = $dataInstagram = [];
		if($instagramToken = $this->getRequest()->getParam('access_token')){
			$host = "https://api.instagram.com/v1/users/self/media/recent/?access_token=".$instagramToken;
			if($this->_iscurl()) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $host);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$content = curl_exec($ch);
				curl_close($ch);
			}else {
				$content = file_get_contents($host);
			}

			$content = json_decode($content, true);
			
			$i=0;
			if($this->getRequest()->getParam('limit')){
				$limit = $this->getRequest()->getParam('limit');
			}else {
				$limit = 20;
			}
			if($this->getRequest()->getParam('resolution')){
				$resolution = $this->getRequest()->getParam('resolution');
			}else {
				$resolution = 'low_resolution';
			}
			if(isset($content['data']) && count($content['data']) > 0){
				foreach($content['data'] as $data){
					$i++;
					$dataInstagram[] = [
						'src' => $data['images'][$resolution]['url'],
						'link' => $data['link'],
						'like' => $data['likes']['count'],
						'comment' => $data['comments']['count']
					];
					if(($limit!='') && ($limit!=0) && ($i==$limit)){
						break;
					}
				}
				$result['status'] = true;
				$result['data'] = serialize($dataInstagram);
				$result['message'] = '';
			}else {
				$result['status'] = false;
				$result['data'] = '';
				$result['message'] = __('Can\'t get data from Instagram. Please check your Access Token and Application config');
			}
		}
		
		$resultJson = $this->resultJsonFactory->create();
		$resultJson->setData($result);
		
		return $resultJson;
    }
	
	public function _iscurl(){
		if(function_exists('curl_version')) {
			return true;
		} else {
			return false;
		}
	}
}
