<?php

namespace Vnecoms\VendorsCms\Model\Rule\Action;

class Collection extends \Magento\Rule\Model\Action\Collection
{
    /**
     * Collection constructor.
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Rule\Model\ActionFactory $actionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Rule\Model\ActionFactory $actionFactory,
        array $data = []
    ) {
        parent::__construct($assetRepo, $layout, $actionFactory, $data);
        $this->setType('Vnecoms\VendorsCms\Model\Rule\Action\Collection');
    }

    /**
     * @return $this
     */
    public function getAttributeElement()
    {
        return $this->getForm()->addField(
            'action:'.$this->getId().':attribute',
            'select',
            [
                'name' => $this->elementName.'[actions]['.$this->getId().'][attribute]',
                'values' => $this->getAttributeSelectOptions(),
                'value' => $this->getAttribute(),
                'value_name' => $this->getAttributeName(),
            ]
        )->setRenderer(
            $this->_layout->getBlockSingleton('Vnecoms\VendorsCms\Block\Rule\Editable')
        );
    }

    /**
     * @return $this
     */
    public function getOperatorElement()
    {
        return $this->getForm()->addField(
            'action:'.$this->getId().':operator',
            'select',
            [
                'name' => $this->elementName.'[actions]['.$this->getId().'][operator]',
                'values' => $this->getOperatorSelectOptions(),
                'value' => $this->getOperator(),
                'value_name' => $this->getOperatorName(),
            ]
        )->setRenderer(
            $this->_layout->getBlockSingleton('Vnecoms\VendorsCms\Block\Rule\Editable')
        );
    }

    /**
     * @return $this
     */
    public function getValueElement()
    {
        return $this->getForm()->addField(
            'action:'.$this->getId().':value',
            'text',
            [
                'name' => $this->elementName.'[actions]['.$this->getId().'][value]',
                'value' => $this->getValue(),
                'value_name' => $this->getValueName(),
            ]
        )->setRenderer(
            $this->_layout->getBlockSingleton('Vnecoms\VendorsCms\Block\Rule\Editable')
        );
    }

    /**
     * @return string
     */
    public function getAddLinkHtml()
    {
        $src = $this->_assetRepo->getUrl('Vnecoms_VendorsCms::images/rule_component_add.gif');
        $html = '<img src="'.$src.'" alt="" class="rule-param-add v-middle" />';

        return $html;
    }

    /**
     * @return string
     */
    public function getRemoveLinkHtml()
    {
        $src = $this->_assetRepo->getUrl('Vnecoms_VendorsCms::images/rule_component_remove.gif');
        $html = '<span class="rule-param"><a href="javascript:void(0)" class="rule-param-remove"><img src="'.
            $src.
            '" alt="" class="v-middle" /></a></span>';

        return $html;
    }
}
