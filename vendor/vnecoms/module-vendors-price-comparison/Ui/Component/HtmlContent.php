<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Ui\Component;

class HtmlContent extends \Magento\Ui\Component\HtmlContent
{
    /**
     * @inheritDoc
     */
    public function getConfiguration()
    {
        $configuration = \Magento\Ui\Component\AbstractComponent::getConfiguration();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();

        $registry = $object_manager->get('\Magento\Framework\Registry');

        if($registry->registry('select_and_sell')){
            $this->setData('wrapper/canShow',false);
            $wrapper = $this->getData('wrapper');
            $wrapper["canShow"] = false;
            $this->setData('wrapper',$wrapper);
        }

        if ($this->getData('wrapper/canShow') !== false) {
            if ($this->getData('isAjaxLoaded')) {
                $configuration['url'] = $this->getData('url');
            } else {
                if (!$this->getData('config/content')) { //add html block cony into cache
                    $content = $this->block->toHtml();
                    $this->addData(['config' => ['content' => $content]]);
                }

                $configuration['content'] = $this->getData('config/content');
            }
            if ($this->getData('wrapper')) {
                $configuration = array_merge($this->getData(), $this->getData('wrapper'));
            }
        }
        return $configuration;
    }
}
