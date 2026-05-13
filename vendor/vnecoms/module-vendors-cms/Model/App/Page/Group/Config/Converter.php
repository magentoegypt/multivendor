<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert($source)
    {
        $pageTypes = [];
        $xpath = new \DOMXPath($source);

        /* @var $widget \DOMNode */
        foreach ($xpath->query('/cms_page_group/group') as $type) {
            $typeAttributes = $type->attributes;

            $id = $typeAttributes->getNamedItem('id')->nodeValue;
            $label = $typeAttributes->getNamedItem('label')->nodeValue;
            $class = $typeAttributes->getNamedItem('class')->nodeValue;
            $order = $typeAttributes->getNamedItem('sort_order')->nodeValue;

            $pageArray = ['id' => $id, 'label' => $label, 'class' => $class, 'sort_order' => $order];

            $pageTypes[$id] = $pageArray;
        }

        return $pageTypes;
    }
}
