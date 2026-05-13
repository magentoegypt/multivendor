<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Tabs;

use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group\Row\Renderer as RowRenderer;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group\Renderer as GroupRenderer;
use Amasty\CheckoutCore\Model\FormManagement;

class AbstractTab extends Generic implements TabInterface
{
    /**
     * @var Group
     */
    protected $groupRows;

    /**
     * @var FormManagement
     */
    protected $formManagement;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Group $groupRows,
        FormManagement $formManagement,
        array $data = []
    ) {
        $this->groupRows = $groupRows;
        $this->formManagement = $formManagement;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        $layout = $this->getLayout();
        $nameInLayout = $this->getNameInLayout();

        $this->groupRows->setRowRenderer(
            $layout->createBlock(
                RowRenderer::class,
                $nameInLayout . '_row_element'
            )
        );

        $this->groupRows->setGroupRenderer(
            $layout->createBlock(
                GroupRenderer::class,
                $nameInLayout . '_group_element'
            )
        );

        return parent::_prepareLayout();
    }
}
