<?php

namespace MGS\Blog\Model\System\Config;

use Magento\Framework\Option\ArrayInterface;

class Show implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'blog_grid',
                'label' => __('Blog Grid')
            ],
            [
                'value' => 'blog_list',
                'label' => __('Blog List')
            ]
        ];
    }

}
