<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Controller\Adminhtml\Section;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use MagentoEgypt\HubApp\Model\Home\SectionFactory;

/**
 * Grid inline edit: position, active, and the two titles. Nothing else is
 * editable from the grid; the form owns every other field.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MagentoEgypt_HubApp::home_sections';

    private const EDITABLE = ['position', 'is_active', 'title_en', 'title_ar'];

    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly SectionFactory $sectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $json = $this->jsonFactory->create();
        $items = $this->getRequest()->getParam('items', []);
        if (!$this->getRequest()->getParam('isAjax') || !is_array($items) || !$items) {
            return $json->setData(['messages' => [__('Please correct the data sent.')], 'error' => true]);
        }

        $messages = [];
        $error = false;
        foreach ($items as $id => $values) {
            $id = (int) $id;
            $section = $this->sectionFactory->create()->load($id);
            if (!$section->getId() || !is_array($values)) {
                $messages[] = __('Section %1 no longer exists.', $id);
                $error = true;
                continue;
            }

            $clean = array_intersect_key($values, array_flip(self::EDITABLE));
            if (array_key_exists('position', $clean)) {
                $clean['position'] = (int) $clean['position'];
            }
            if (array_key_exists('is_active', $clean)) {
                $clean['is_active'] = (int) $clean['is_active'] ? 1 : 0;
            }
            foreach (['title_en', 'title_ar'] as $title) {
                if (array_key_exists($title, $clean)) {
                    $value = trim((string) $clean[$title]);
                    $clean[$title] = $value === '' ? null : mb_substr($value, 0, 255);
                }
            }

            try {
                $section->addData($clean)->save();
            } catch (\Throwable $e) {
                $messages[] = __('Section %1: %2', $id, $e->getMessage());
                $error = true;
            }
        }

        return $json->setData(['messages' => $messages, 'error' => $error]);
    }
}
