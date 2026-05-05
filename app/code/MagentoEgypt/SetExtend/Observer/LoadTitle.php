<?php 
namespace MagentoEgypt\SetExtend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class LoadTitle implements ObserverInterface
{
    protected $helper;

    public function __construct(
        \MagentoEgypt\SetExtend\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        $collection = $observer->getCollection();
        if($collection instanceof \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection) {
            foreach($collection as $setObject) {
                if($setObject->getId() > 0) {
                    $data = $this->helper->loadSetTitles( $setObject->getId() );
                    if(count($data)>0) {
                        $extensionAttributes = $setObject->getExtensionAttributes();
                        $extensionAttributes->setFrontendLabels($data);
                        $setObject->setExtensionAttributes($extensionAttributes);
                    }
                }
            }
        }
    }
}