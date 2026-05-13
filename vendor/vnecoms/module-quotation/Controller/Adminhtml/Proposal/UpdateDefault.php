<?php


namespace Vnecoms\Quotation\Controller\Adminhtml\Proposal;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class UpdateDefault extends \Magento\Backend\App\Action implements HttpPostActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $itemId = $this->getRequest()->getParam('item_id');
        $defaultProposalId = $this->getRequest()->getParam('proposal_id');
        try {
            $item = $this->_objectManager->create('Vnecoms\Quotation\Model\Item')->load($itemId);
            foreach($item->getProposalsCollection() as $proposal){
                if($proposal->getIsDefault() && $proposal->getId() != $defaultProposalId){
                    $proposal->setIsDefault(0)->save();
                }elseif($proposal->getId() == $defaultProposalId){
                    $proposal->setIsDefault(1)->save();
                }
            }

            $response->setData([
                'error' => false,
            ]);
        } catch (LocalizedException $e) {
            $response->setData([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $response->setData([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->_resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
