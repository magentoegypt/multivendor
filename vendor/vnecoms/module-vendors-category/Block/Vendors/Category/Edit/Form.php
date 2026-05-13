<?php

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Edit;

/**
 * Class Form.
 */
class Form extends \Vnecoms\VendorsCategory\Block\Vendors\Category\AbstractCategory
{
    /**
     * Additional buttons on category page.
     *
     * @var array
     */
    protected $_additionalButtons = [];

    /**
     * @var string
     */
    protected $_template = 'category/edit/form.phtml';

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $vendorSession, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $category = $this->getCategory();
        $categoryId = (int) $category->getId();
        // 0 when we create category, otherwise some value for editing category

        $this->setChild(
            'tabs',
            $this->getLayout()->createBlock('Vnecoms\VendorsCategory\Block\Vendors\Category\Tabs', 'tabs')
        );

        // Save button
            $this->addButton(
                'save',
                [
                    'id' => 'save',
                    'label' => __('Save Category'),
                    'class' => 'save primary save-category btn-success',
                    'data_attribute' => [
                        'mage-init' => [
                            'Vnecoms_VendorsCategory/category/edit' => [
                                'url' => $this->getSaveUrl(),
                                'ajax' => true,
                            ],
                        ],
                    ],
                ]
            );

        // Delete button
        if ($categoryId && $categoryId != $this->getRootId()) {
            $this->addButton(
                'delete',
                [
                    'id' => 'delete',
                    'label' => __('Delete Category'),
                    'onclick' => "categoryDelete('".$this->getDeleteUrl()."')",
                    'class' => 'delete btn-github',
                ]
            );
        }

        // Reset button
            $resetPath = $categoryId ? 'catalog/*/edit' : 'catalog/*/add';
        $this->addButton(
            'reset',
            [
                    'id' => 'reset',
                    'label' => __('Reset'),
                    'onclick' => "categoryReset('"
                        .$this->getUrl($resetPath, $this->getDefaultUrlParams())
                        ."',true)",
                    'class' => 'reset btn-default',
                ]
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    /**
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }

    /**
     * @return string
     */
    public function getResetButtonHtml()
    {
        return $this->getChildHtml('reset_button');
    }

    /**
     * Retrieve additional buttons html.
     *
     * @return string
     */
    public function getAdditionalButtonsHtml()
    {
        $html = '';
        foreach ($this->_additionalButtons as $childName) {
            $html .= $this->getChildHtml($childName);
        }

        return $html;
    }

    /**
     * Add additional button.
     *
     * @param string $alias
     * @param array  $config
     *
     * @return $this
     */
    public function addAdditionalButton($alias, $config)
    {
        if (isset($config['name'])) {
            $config['element_name'] = $config['name'];
        }
        if ($this->hasToolbarBlock()) {
            $this->addButton($alias, $config);
        } else {
            $this->setChild(
                $alias.'_button',
                $this->getLayout()->createBlock('Vnecoms\Vendors\Block\Vendors\Widget\Button')->addData($config)
            );
            $this->_additionalButtons[$alias] = $alias.'_button';
        }

        return $this;
    }

    /**
     * Remove additional button.
     *
     * @param string $alias
     *
     * @return $this
     */
    public function removeAdditionalButton($alias)
    {
        if (isset($this->_additionalButtons[$alias])) {
            $this->unsetChild($this->_additionalButtons[$alias]);
            unset($this->_additionalButtons[$alias]);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTabsHtml()
    {
        return $this->getChildHtml('tabs');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getHeader()
    {
        if ($this->getCategoryId()) {
            return $this->getCategoryName();
        } else {
            $parentId = (int) $this->getRequest()->getParam('parent');
            if ($parentId && $parentId != $this->getRootId()) {
                return __('New Subcategory');
            } else {
                return __('New Root Category');
            }
        }
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function getDeleteUrl(array $args = [])
    {
        $params = array_merge($this->getDefaultUrlParams(), $args);

        return $this->getUrl('catalog/*/delete', $params);
    }

    /**
     * Return URL for refresh input element 'path' in form.
     *
     * @param array $args
     *
     * @return string
     */
    public function getRefreshPathUrl(array $args = [])
    {
        $params = array_merge($this->getDefaultUrlParams(), $args);

        return $this->getUrl('catalog/*/refreshPath', $params);
    }

    /**
     * @return string
     */
    public function getProductsJson()
    {
        $products = $this->getCategory()->getProductsPosition();
        if (!empty($products)) {
            return $this->_jsonEncoder->encode($products);
        }

        return '{}';
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->_request->isXmlHttpRequest() || $this->_request->getParam('isAjax');
    }

    /**
     * Get parent category id.
     *
     * @return int
     */
    public function getParentCategoryId()
    {
        return (int) $this->templateContext->getRequest()->getParam('parent');
    }

    /**
     * Get category id.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return (int) $this->templateContext->getRequest()->getParam('id');
    }

    /**
     * Add button block as a child block or to global Page Toolbar block if available.
     *
     * @param string $buttonId
     * @param array  $data
     *
     * @return $this
     */
    protected function addButton($buttonId, array $data)
    {
        $childBlockId = $buttonId.'_button';
        $button = $this->getButtonChildBlock($childBlockId);
        $button->setData($data);
        $block = $this->getLayout()->getBlock('page.actions.toolbar');
        if ($block) {
            $block->setChild($childBlockId, $button);
        } else {
            $this->setChild($childBlockId, $button);
        }
    }

    /**
     * @return bool
     */
    protected function hasToolbarBlock()
    {
        return $this->getLayout()->isBlock('page.actions.toolbar');
    }

    /**
     * Adding child block with specified child's id.
     *
     * @param string      $childId
     * @param null|string $blockClassName
     *
     * @return \Magento\Backend\Block\Widget
     */
    protected function getButtonChildBlock($childId, $blockClassName = null)
    {
        if (null === $blockClassName) {
            $blockClassName = 'Vnecoms\Vendors\Block\Vendors\Widget\Button';
        }

        return $this->getLayout()->createBlock($blockClassName, $this->getNameInLayout().'-'.$childId);
    }

    /**
     * @return array
     */
    protected function getDefaultUrlParams()
    {
        return ['_current' => true, '_query' => ['isAjax' => null]];
    }
}
