<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Gift Wrap for Magento 2 (System)
 */

namespace Amasty\CheckoutGiftWrap\Model;

use Amasty\CheckoutGiftWrap\Api\GiftMessageInformationManagementInterface;
use Magento\GiftMessage\Api\CartRepositoryInterface;
use Magento\GiftMessage\Api\ItemRepositoryInterface;
use Magento\GiftMessage\Model\MessageFactory;

class GiftMessageInformationManagement implements GiftMessageInformationManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var ItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ItemRepositoryInterface $itemRepository,
        MessageFactory $messageFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->itemRepository = $itemRepository;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param string $cartId
     * @param mixed $giftMessages
     * @return bool
     */
    public function update($cartId, $giftMessages): bool
    {
        foreach ($giftMessages as $messageData) {
            /** @var \Magento\GiftMessage\Model\Message $message */
            $message = $this->messageFactory->create();

            $message->setData([
                'message' => $messageData['message'],
                'sender' => $messageData['sender'],
                'recipient' => $messageData['recipient'],
            ]);

            try {
                if ($messageData['item_id'] == Messages::QUOTE_MESSAGE_INDEX) {
                    $this->cartRepository->save($cartId, $message);
                } else {
                    $this->itemRepository->save($cartId, $message, $messageData['item_id']);
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}
