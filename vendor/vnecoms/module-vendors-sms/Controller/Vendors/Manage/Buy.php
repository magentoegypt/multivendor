<?php
namespace Vnecoms\VendorsSms\Controller\Vendors\Manage;

use Vnecoms\VendorsSms\Model\CreditProcessor\Sms;
class Buy extends \Vnecoms\Vendors\Controller\Vendors\Action
{

    /**
     * @return void
     */
    public function execute()
    {
        $creditPackage = (int)$this->getRequest()->getPost('sms_credit_package');
        $helper = $this->_objectManager->create('Vnecoms\VendorsSms\Helper\Data');
        $availablePackages = $helper->getSmsCreditPackages();
        try{
            if(isset($availablePackages[$creditPackage])){
                $price = isset($availablePackages[$creditPackage]['price'])?$availablePackages[$creditPackage]['price']:0;
                $vendor = $this->_session->getVendor();
               
                /*Create transaction to subtract the credit.*/
                $creditAccount = $this->_objectManager->create('Vnecoms\Credit\Model\Credit');
                $creditAccount->loadByCustomerId($this->_session->getCustomerId());
                $data = [
                    'vendor' => $vendor,
                    'type' => Sms::TYPE,
                    'amount' => $price,
                ];
                $creditProcessor = $this->_objectManager->create('Vnecoms\Credit\Model\Processor');
                $creditProcessor->process($creditAccount, $data);
                
                /*Add Credit To SMS Credit Account*/
                $vendor->setSmsCredit($vendor->getSmsCredit() + $creditPackage)->save();
                /* Save SMS credit transaction*/
                $transaction = $this->_objectManager->create('Vnecoms\VendorsSms\Model\Transaction');
                $transaction->setData([
                    'vendor_id' => $vendor->getId(),
                    'amount'    => $creditPackage,
                    'balance'   => $vendor->getSmsCredit(),
                    'description' => __("You bought SMS credit"),
                ])->save();
            }else{
                $this->messageManager->addError('The requested credit package is not available.');
            }
        }catch (\Exception $e){
            $this->messageManager->addError($e->getMessage());
        }
        
        $this->_redirect('sms/manage');
    }
}
