<?php

namespace Vnecoms\VendorsCategory\Model\Template;

use Magento\Framework\App\ObjectManager;

class CategorySidebarFilter extends \Magento\Email\Model\Template\Filter
{
    /**
     * Retrieve vendor block directive.
     *
     * @param array $construction
     *
     * @return string
     */
    public function catsidebarDirective($construction)
    {
		$om = ObjectManager::getInstance();
        $skipParams = array('id');
		$tokenizeParams = $om->create('Magento\Framework\Filter\Template\Tokenizer\Parameter');
        $tokenizeParams->setString($construction[2]);
        $params = $tokenizeParams->tokenize();
		
        $layout = $om->create('Magento\Framework\View\LayoutInterface');

        $featuredBlock = $layout->createBlock('Vnecoms\VendorsCategory\Block\App\CategorySidebar');
        if (isset($params['template']) && $params['template']) {
            $featuredBlock->setTemplate($params['template']);
        }else{
			$featuredBlock->setTemplate('Vnecoms_VendorsCategory::widget/sidebar.phtml');
		}
		
        if ($params && is_array($params)) {
            $featuredBlock->setFeaturedParams($params);
            foreach ($params as $k => $v) {
                if (in_array($k, $skipParams)) {
                    continue;
                }
                $featuredBlock->setDataUsingMethod($k, $v);
            }
        }

        return $featuredBlock->toHtml();
    }
}


