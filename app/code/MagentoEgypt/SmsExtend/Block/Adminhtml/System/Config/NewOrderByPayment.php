<?php 
namespace MagentoEgypt\SmsExtend\Block\Adminhtml\System\Config;

class NewOrderByPayment extends \Vnecoms\Sms\Block\Adminhtml\System\Config\NewOrderByPayment
{
    protected $messageIdColumnsRenderer;

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentMethodColumnsRenderer()
        ]);
        $this->addColumn('message',[
            'label' => __('Message'),
            'renderer' => $this->getMessageColumnsRenderer()
        ]);
        $this->addColumn('message_id',[
            'label' => __('WhatsApp Template'),
            'renderer' => $this->getMessageIdColumnsRenderer()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Message');
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getMessageIdColumnsRenderer()
    {
        if (null === $this->messageIdColumnsRenderer) {
            $element = $this->getElement();
            $uniqId = md5($element->getHtmlId() . $element->getScope() . $element->getScopeId());
            $this->messageIdColumnsRenderer = $this->getLayout()->createBlock(
                MessagesIdColumns::class,
                'vnecoms_sms_system_config_new_order_message_id_columns_' . $uniqId,
                [
                    'data' => [
                        'is_render_to_js_template' => true,
                        'uiid' => $uniqId
                    ]
                ]
            );
        }
        return $this->messageIdColumnsRenderer;
    }
}