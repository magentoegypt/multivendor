<?php
namespace Vnecoms\VendorsAvatarProfile\Controller\Avatar;

class Current extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $_helperData;

    /**
     * Current constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
    ) {
        $this->_helperData = $_helperData;
        parent::__construct($context);
    }

    /**
     * @return array|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @codingStandardsIgnoreStart
     */
    public function execute()
    {
        try{
            $result["url"] = $this->_helperData->getAttachmentUrl();
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            $result = ['error' => __("Something wrong. Please try upload again"), 'errorcode' => $e->getCode()];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($result)
        );
    }
}
