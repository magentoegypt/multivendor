<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 22/11/2016
 * Time: 10:17.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Block;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;

    /** @var \Vnecoms\VendorsCms\Helper\Data  */
    protected $_dataHelper;

    /** @var \Vnecoms\VendorsCms\Model\BlockFactory  */
    protected $blockFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context  $context
     * @param \Magento\Framework\Registry            $registry
     * @param \Vnecoms\VendorsCms\Model\BlockFactory $blockFactory
     * @param array                                  $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCms\Model\BlockFactory $blockFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->blockFactory = $blockFactory;
    }

    protected function _construct()
    {
        $this->_objectId = 'block_id';
        $this->_blockGroup = 'Vnecoms_VendorsCms'; //declare before controller
        $this->_controller = 'vendors_block';

        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save btn btn-primary',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ],
            ],
            -100
        );
        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Block'));

        $this->buttonList->update('delete', 'label', __('Delete Block'));
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later.
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('cms/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Get edit form container banner text.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('cms_bock')->getId()) {
            return __("Edit Block '%1'", $this->escapeHtml($this->getBlock()->getTitle()));
        } else {
            return __('New Block');
        }
    }

    /**
     * check action allowed.
     *
     * @param $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Retrieve the block.
     *
     * @return \Vnecoms\VendorsCms\Model\Block|null
     */
    public function getBlock()
    {
        $block = null;
        $blockId = $this->getRequest()->getParam('block_id');
        if ($blockId) {
            $model = $this->blockFactory->create();
            $block = $model->load($blockId);
        }

        return $block;
    }
}
