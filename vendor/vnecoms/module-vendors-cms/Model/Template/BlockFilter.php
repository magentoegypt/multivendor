<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

/**
 * Cms Template Filter Model.
 */
class BlockFilter extends AbstractFilter
{
    /**
     * Retrieve vendor block directive.
     *
     * @param array $construction
     *
     * @return string
     */
    public function blockDirective($construction)
    {
        $skipParams = ['id'];
        $this->tokenizeParams->setString($construction[2]);
        $blockParameters = $this->tokenizeParams->tokenize();
        //echo"<pre>";var_dump($blockParameters);exit;
        $layout = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\View\LayoutInterface');

        $block = $layout->createBlock('Vnecoms\VendorsCms\Block\Block');
        if ($block) {
            $block->setBlockId($blockParameters['block_id']);
        }
        if ($block) {
            $block->setBlockParams($blockParameters);
            foreach ($blockParameters as $k => $v) {
                if (in_array($k, $skipParams)) {
                    continue;
                }
                $block->setDataUsingMethod($k, $v);
            }
        }
        if (!$block) {
            return '';
        }

        return $block->toHtml();
    }
}
