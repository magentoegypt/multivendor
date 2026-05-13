<?php
namespace Vnecoms\VendorsAvatarProfile\Controller\Vendors\Index;

class Current extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $_helperData;

    /**
     * Current constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $_helperData
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
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
            $result["url"] = $this->_helperData->getAttachmentVendorUrl();
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
