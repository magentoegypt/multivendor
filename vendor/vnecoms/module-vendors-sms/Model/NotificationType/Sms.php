<?php

namespace Vnecoms\VendorsSms\Model\NotificationType;

use Vnecoms\VendorsProfileNotification\Model\Type\AbstractType;
use Vnecoms\VendorsProfileNotification\Model\Process;
use Vnecoms\Vendors\Model\Vendor;
use Magento\Framework\Data\Form;
use Vnecoms\Vendors\Model\UrlInterface;

class Sms extends AbstractType
{
    const CODE              = 'type_sms';
    
    /**
     * @var \Magento\Framework\Url
     */
    protected $frontendUrl;
    
    /**
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Url $frontendUrl
     */
    public function __construct(
        UrlInterface $urlBuilder,
        \Magento\Framework\Url $frontendUrl
    ) {
        $this->frontendUrl = $frontendUrl;
        parent::__construct($urlBuilder);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::getTitle()
     */
    public function getTitle(){
        return __('Vendor Sms');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::prepareForm()
     */
    public function prepareForm(
        Form $form,
        Process $process
    ){
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->addField(
            'sms_credit',
            'text',
            [
                'name' => 'sms_credit',
                'label' => __('Sms Credit Less Than:'),
                'title' => __('Sms Credit Less Than:'),
                'class' => 'process_type_field '.self::CODE,
                'required' => true
            ],
            'type'
        );
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::beforeSaveProcess()
     */
    public function beforeSaveProcess(
        Process $process
    ){
        $condition = $process->getData('sms_credit');
        if(!$condition) return;
        $process->setData('additional_data', $condition);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::afterLoadProcess()
     */
    public function afterLoadProcess(
        Process $process
    ){
        $condition = $process->getData('additional_data');
        $process->setData('sms_credit', $condition);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::isCompletedProcess()
     */
    public function isCompletedProcess(Process $process, Vendor $vendor){
        return $vendor->getSmsCredit() > $process->getAdditionalData();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\VendorsProfileNotification\Model\Type\AbstractType::getUrl()
     */
    public function getUrl(Process $process){
        return $this->urlBuilder->getUrl('sms/manage');
    }
}
