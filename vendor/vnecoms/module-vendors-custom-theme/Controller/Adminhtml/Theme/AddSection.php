<?php
namespace Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Theme;

use Vnecoms\VendorsCustomTheme\Controller\Adminhtml\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class AddSection extends Action
{        
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultJsonFactory    = $resultJsonFactory;
        parent::__construct($context, $registry);
    }
    /**
     * @return void
     */
    public function execute()
    {
        $response   = new \Magento\Framework\DataObject();
        
        if ($this->getRequest()->getPostValue()) {
            try {
                /** @var \Vnecoms\VendorsCustomTheme\Model\Theme $model */
                $model = $this->_objectManager->create(
                    'Vnecoms\VendorsCustomTheme\Model\Theme'
                );

                $id = $this->getRequest()->getParam('theme');
                
                $model->load($id);
                if (!$id || ($id != $model->getId())) {
                    throw new LocalizedException(__('Wrong theme specified.'));
                }
                                
                
                $model->addSection($this->getRequest()->getParam('section'));
                $response->setData([
                    'error' => false,
                ]);
            } catch (LocalizedException $e) {
                $response->setData([
                    'error' => true,
                    'msg' => $e->getMessage()
                ]);
            } catch (\Exception $e) {
                $response->setData([
                    'error' => true,
                    'msg' => $e->getMessage()
                ]);
            }
        }else{
            $response->setData([
                'error' => true,
                'msg' => __("POST data is not valid.")
            ]);
        }
        
        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
