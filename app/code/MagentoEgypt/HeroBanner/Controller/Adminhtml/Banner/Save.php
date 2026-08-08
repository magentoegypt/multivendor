<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\HeroBanner\Model\Banner;
use MagentoEgypt\HeroBanner\Model\BannerFactory;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HeroBanner::banner';

    public function __construct(
        Action\Context $context,
        private readonly BannerFactory $bannerFactory,
        private readonly ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $data     = $this->getRequest()->getPostValue();

        if (!$data) {
            return $redirect->setPath('*/*/');
        }

        $id     = (int) ($data['banner_id'] ?? 0);
        $banner = $this->bannerFactory->create();

        if ($id) {
            $banner->load($id);
            if (!$banner->getId()) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));

                return $redirect->setPath('*/*/');
            }
        } else {
            unset($data['banner_id']);
        }

        $data['image'] = $this->resolveImage($data['image'] ?? null);

        /*
         * Whitelist. The form posts `form_key` and UI-component bookkeeping
         * alongside the real fields, and passing the raw POST into a model that
         * saves every key it holds is how stray columns and mass-assignment bugs
         * get in.
         */
        $allowed = [
            'banner_id', 'slot', 'title', 'kicker', 'subtitle', 'cta_label',
            'image', 'url', 'sort_order', 'is_active', 'store_id',
        ];
        $clean = array_intersect_key($data, array_flip($allowed));

        if (trim((string) ($clean['title'] ?? '')) === '') {
            $this->messageManager->addErrorMessage(__('A banner needs a headline.'));

            return $redirect->setPath('*/*/edit', ['banner_id' => $id]);
        }

        if (!in_array($clean['slot'] ?? '', [Banner::SLOT_HERO, Banner::SLOT_TILE], true)) {
            $clean['slot'] = Banner::SLOT_HERO;
        }

        try {
            $banner->addData($clean)->save();
            $this->messageManager->addSuccessMessage(__('The banner has been saved.'));
            $this->_getSession()->setFormData(false);

            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['banner_id' => $banner->getId()]);
            }

            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Could not save the banner.'));
        }

        $this->_getSession()->setFormData($data);

        return $redirect->setPath('*/*/edit', ['banner_id' => $id]);
    }

    /**
     * The image field posts as the UI file-uploader's array form. A freshly
     * uploaded file is still in hero/tmp and has to be moved before it is
     * referenced by a saved row; an untouched one comes back with the path it
     * already had and must be left alone.
     */
    private function resolveImage(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_array($value)) {
            return trim((string) $value);
        }

        $name = (string) ($value['name'] ?? '');

        if (!empty($value['tmp_name']) || ($value['type'] ?? '') === 'image/tmp') {
            try {
                return 'hero/' . $this->imageUploader->moveFileFromTmp($name);
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage(__('The image could not be saved: %1', $e->getMessage()));

                return '';
            }
        }

        /*
         * An existing image comes back with a full URL in `url`; the column
         * stores a media-relative path, so the media base is stripped back off.
         */
        $existing = (string) ($value['url'] ?? $name);
        if (preg_match('~/media/(.+)$~', $existing, $m)) {
            return $m[1];
        }

        return $existing;
    }
}
