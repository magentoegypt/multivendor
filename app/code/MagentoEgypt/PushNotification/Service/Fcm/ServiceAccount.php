<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Service\Fcm;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\SerializerInterface;
use MagentoEgypt\PushNotification\Helper\Config;

class ServiceAccount
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var File
     */
    private $file;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array|null
     */
    private $cached;

    public function __construct(Config $config, File $file, SerializerInterface $serializer)
    {
        $this->config = $config;
        $this->file = $file;
        $this->serializer = $serializer;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function load(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $path = $this->config->getServiceAccountPath();
        if (!$path) {
            throw new LocalizedException(__('Firebase service account JSON path is not configured.'));
        }

        if (!$this->file->isExists($path)) {
            throw new LocalizedException(__('Firebase service account JSON not found at "%1".', $path));
        }

        if (!$this->file->isReadable($path)) {
            throw new LocalizedException(__('Firebase service account JSON at "%1" is not readable.', $path));
        }

        $contents = $this->file->fileGetContents($path);
        try {
            $data = $this->serializer->unserialize($contents);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Firebase service account JSON could not be parsed: %1', $e->getMessage()));
        }

        foreach (['client_email', 'private_key'] as $required) {
            if (empty($data[$required])) {
                throw new LocalizedException(__('Firebase service account JSON is missing "%1".', $required));
            }
        }

        $this->cached = $data;
        return $data;
    }
}
