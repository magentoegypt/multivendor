<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsTranslateInline\Controller\Vendors\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Translate extends \Vnecoms\Vendors\Controller\Vendors\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Translate\Inline\ParserInterface
     */
    protected $inlineParser;

    /**
     * Index constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Translate\Inline\ParserInterface $inlineParser
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Translate\Inline\ParserInterface $inlineParser
    ) {
        parent::__construct($context);

        $this->inlineParser = $inlineParser;
    }

    /**
     * Ajax action for inline translation
     *
     * @return void
     */
    public function execute()
    {
        $translate = (array)$this->getRequest()->getPost('translate');

        try {
            $response = $this->inlineParser->processAjaxPost($translate);
        } catch (\Exception $e) {
            $response = "{error:true,message:'" . $e->getMessage() . "'}";
        }
        $this->getResponse()->representJson(json_encode($response));
        $this->_actionFlag->set('', self::FLAG_NO_POST_DISPATCH, true);
    }
}
