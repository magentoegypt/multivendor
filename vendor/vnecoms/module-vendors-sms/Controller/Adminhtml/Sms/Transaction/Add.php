<?php
namespace Vnecoms\VendorsSms\Controller\Adminhtml\Sms\Transaction;

class Add extends \Magento\Backend\App\Action
{
    /**
     * @return void
     */
    public function execute()
    {
        $resultJson = $this->_objectManager->create('Magento\Framework\Controller\Result\Json');
        try{
            $request    = $this->getRequest();
            $response   = new \Magento\Framework\DataObject();
            
            $creditAmount   = $request->getParam('credit',0);
            $vendorId       = $request->getParam('vendor_id');
            $description    = $request->getParam('description');
            
            $vendor = $this->_objectManager->create('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
            /*Substract SMS Credit.*/
            $vendor->setSmsCredit($vendor->getSmsCredit() + $creditAmount)->save();
            
            /* Save SMS credit transaction*/
            $transaction = $this->_objectManager->create('Vnecoms\VendorsSms\Model\Transaction');
            $transaction->setData([
                'vendor_id'     => $vendor->getId(),
                'amount'        => $creditAmount,
                'balance'       => $vendor->getSmsCredit(),
                'description'   => $description?$description:(
                    $creditAmount>0?__("Admin add %1 credit to your SMS credit account",$creditAmount):
                    __("Admin substract %1 credit from your SMS credit account",abs($creditAmount))
                ),
            ])->save();
            
            $response->setData([
                'error' => false,
            ]);
            return $resultJson->setJsonData($response->toJson());
        }catch (\Exception $e){
            $response->setData(['error'=>true, 'msg'=>$e->getMessage()] );
            return $resultJson->setJsonData($response->toJson());
        }
    }
}
