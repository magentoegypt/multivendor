<?php
/**
 * Copyright © Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

class AppFilter extends AbstractFilter
{
    protected $_app;

    /***
     * @return \Vnecoms\VendorsCms\Model\App
     */
    public function getApp()
    {
        if (!$this->_app) {
            $this->_app = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\VendorsCms\Model\App');
        }

        return $this->_app;
    }

    /**
     * Retrieve vendor block directive.
     *
     * @param array $construction
     *
     * @return string
     */
    public function appDirective($construction)
    {
        return $this->generateApp($construction);
    }

    public function generateApp($construction)
    {
        $params = $this->getParameters($construction[2]);

        $realParams = $this->getApp()->load($params['app_id'])->getParameters();
        // Determine what name block should have in layout
        $name = null;
        if (isset($realParams['name'])) {
            $name = $realParams['name'];
        }

        // validate required parameter type or id
        $type = $this->getApp()->getType();

        //var_dump($type);die();
        $xml = $this->getApp()->getAppByClassType($type);
        if ($xml === null) {
            return '';
        }

        if (isset($realParams['conditions'])) {
            if (class_exists(\Magento\Framework\Serialize\Serializer\Json::class)) {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $serializer = $objectManager->create(\Magento\Framework\Serialize\Serializer\Json::class);
                //Use serializer of magento
                $realParams['conditions'] = $serializer->serialize($realParams['conditions']);
            } elseif (!class_exists(\Magento\Framework\Serialize\Serializer\Json::class)) {
                //Use default php serialize
                $realParams['conditions'] = serialize($realParams['conditions']);
            }
        }
        //var_dump($realParams);die();
        // define $app block and check the type is instance of App Interface
        $app = $this->_layout->createBlock($type, $name, ['data' => $realParams]);
        if (!$app instanceof \Vnecoms\VendorsCms\Block\App\AppInterface) {
            return '';
        }

        return $app->toHtml();
    }
}
