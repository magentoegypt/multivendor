<?php 
namespace MagentoEgypt\PortoExtend\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;

class ImportDemo extends \Smartwave\Porto\Model\Import\Demo
{
	protected $theme;
	protected $request;

	public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Vnecoms\VendorsCustomTheme\Model\Theme $theme,
        \Magento\Framework\App\RequestInterface $request
    ) {
        parent::__construct($scopeConfig, $storeManager, $objectManager, $configFactory, $cacheTypeList);
        $this->theme = $theme;
        $this->request = $request;
        $this->_importPath = BP . '/app/code/Smartwave/Porto/etc/import/';
        $this->_parser = new \Magento\Framework\Xml\Parser();
    }

	public function importDemo($demo_version,$store=NULL,$website = NULL)
    {
        // Default response
        $gatewayResponse = new DataObject([
            'is_valid' => false,
            'import_path' => '',
            'request_success' => false,
            'request_message' => __('Error during Import '.$demo_version.'.'),
        ]);

        try {
            $xmlPath = $this->_importPath . $demo_version . '.xml';
            $overwrite = true;
            
            if (!is_readable($xmlPath))
            {
                throw new \Exception(
                    __("Can't get the data file for import ".$demo_version.": ".$xmlPath)
                );
            }
            $data = $this->_parser->load($xmlPath)->xmlToArray();
            $scope = "default";
            $scope_id = 0;
            if ($store && $store > 0) // store level
            {
                $scope = "stores";
                $scope_id = $store;
            }
            elseif ($website && $website > 0) // website level
            {
                $scope = "websites";
                $scope_id = $website;
            }

            $themeId = $this->request->getParam('theme', false);
			if($themeId>0) {
				$theme = $this->theme->load($themeId);
				if($theme->getId()) {
					$section = [];
					foreach($data['root']['config'] as $b_name => $b){
		                foreach($b as $c_name => $c){
		                    foreach($c as $d_name => $d){
		                    	if(is_array($d)) {
		                    		$d = array_shift($d);
		                    	}
		                        if(!empty($d)) $section[$b_name][$c_name]['fields'][$d_name]['value'] = $d;
		                    }
		                }
		            }
		            // var_dump($section);die;
					$theme->setGroups($section);
					$theme->save();
				}

			} else {
	            foreach($data['root']['config'] as $b_name => $b){
	                foreach($b as $c_name => $c){
	                    foreach($c as $d_name => $d){
	                        $this->_configFactory->saveConfig($b_name.'/'.$c_name.'/'.$d_name,$d,$scope,$scope_id);
	                    }
	                }
	            }
			}

            //$gatewayResponse->setData("import_path",$config);
            // $this->_objectManager->get('Smartwave\Porto\Model\Cssconfig\Generator')->generateCss('design','','');
            // $this->_objectManager->get('Smartwave\Porto\Model\Cssconfig\Generator')->generateCss('settings','','');
            $this->_cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);

            $gatewayResponse->setIsValid(true);
            $gatewayResponse->setRequestSuccess(true);

            if ($gatewayResponse->getIsValid()) {
                $gatewayResponse->setRequestMessage(__('Success to Import '.$demo_version.'.'));
            } else {
                $gatewayResponse->setRequestMessage(__('Error during Import '.$demo_version.'.'));
            }
        } catch (\Exception $exception) {
            $gatewayResponse->setIsValid(false);
            $gatewayResponse->setRequestMessage($exception->getMessage());
        }

        return $gatewayResponse;
    }
}