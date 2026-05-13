<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Filter\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert dom node tree to array.
     *
     * @param \DOMDocument $source
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $output = [];
        $xpath = new \DOMXPath($source);
        $filters = $xpath->evaluate('/config/cms_filter');
        /** @var $filterNode \DOMNode */
        foreach ($filters as $filterNode) {
            $data = [];
            $filterId = $this->getAttributeValue($filterNode, 'id');
            $data['filter_id'] = $filterId;
            $data['action_class'] = $this->getAttributeValue($filterNode, 'class');
            /** @var $childNode \DOMNode */
            foreach ($filterNode->childNodes as $childNode) {
                if ($childNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $data[$childNode->nodeName] = $childNode->nodeValue;
              //  $data = $this->convertChild($childNode, $data);
            }
            $output[$filterId] = $data;
        }
        //echo "<pre>";var_dump($output);die();
        return $output;
    }

    /**
     * Get attribute value.
     *
     * @param \DOMNode $input
     * @param string   $attributeName
     * @param mixed    $default
     *
     * @return null|string
     */
    protected function getAttributeValue(\DOMNode $input, $attributeName, $default = null)
    {
        $node = $input->attributes->getNamedItem($attributeName);

        return $node ? $node->nodeValue : $default;
    }

    /**
     * Convert child from dom to array.
     *
     * @param \DOMNode $childNode
     * @param array    $data
     *
     * @return array
     */
    protected function convertChild(\DOMNode $childNode, $data)
    {
        /** @var $subscription \DOMNode */
        foreach ($childNode->childNodes as $subscription) {
            if ($subscription->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
        }

        return $data;
    }
}
