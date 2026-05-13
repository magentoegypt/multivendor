<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class Qreponse extends Request
{
    /**
     * @return void
     */
    public function execute()
    {
        $reponseId = $this->getRequest()->getParam('id', 0);
        $reponse = $this->_objectManager->create('Vnecoms\RMA\Model\Reponse')->load($reponseId);
        $this->getResponse()->setBody($reponse->getContent());
    }
}
