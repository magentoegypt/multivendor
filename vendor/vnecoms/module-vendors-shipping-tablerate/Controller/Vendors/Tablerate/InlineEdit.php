<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

use Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends \Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate
{

    protected $rate ;
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $rateId) {
            $this->setRate($this->rateRepository->getById($rateId));

            $this->updateRate($postItems[$rateId], true);

            $this->saveRate($this->getRate());
        }

        return $resultJson->setData([
            'messages' => $this->getErrorMessages(),
            'error' => $this->isErrorExists()
        ]);
    }



    /**
     * Update rate data
     *
     * @param array $data
     * @return void
     */
    protected function updateRate(array $data)
    {
        $rate = $this->getRate();
        $rateData = array_merge(
            $rate->getData(),
            $data
        );
        $this->dataObjectHelper->populateWithArray(
            $rate,
            $rateData,
            '\Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface'
        );
    }

    /**
     * Save rate with error catching
     *
     * @param TablerateInterface $rate
     * @return void
     */
    protected function saveRate(TablerateInterface $rate)
    {
        try {
            $this->rateRepository->save($rate);
        } catch (\Magento\Framework\Exception\InputException $e) {
            $this->getMessageManager()->addError($this->getErrorWithRateId($e->getMessage()));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->getMessageManager()->addError($this->getErrorWithRateId($e->getMessage()));
        } catch (\Exception $e) {
            $this->getMessageManager()->addError($this->getErrorWithRateId('We can\'t save the rate.'));
        }
    }


    /**
     * Get array with errors
     *
     * @return array
     */
    protected function getErrorMessages()
    {
        $messages = [];
        foreach ($this->getMessageManager()->getMessages()->getItems() as $error) {
            $messages[] = $error->getText();
        }
        return $messages;
    }

    /**
     * Check if errors exists
     *
     * @return bool
     */
    protected function isErrorExists()
    {
        return (bool)$this->getMessageManager()->getMessages(true)->getCount();
    }

    /**
     * Set rate
     *
     * @param TablerateInterface $rate
     * @return $this
     */
    protected function setRate(TablerateInterface $rate)
    {
        $this->rate = $rate;
        return $this;
    }

    /**
     * Receive rate
     *
     * @return TablerateInterface
     */
    protected function getRate()
    {
        return $this->rate;
    }

    /**
     * Add page title to error message
     *
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithRateId($errorText)
    {
        return '[Rate ID: ' . $this->getRate()->getId() . '] ' . __($errorText);
    }
}
