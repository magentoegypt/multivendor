<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Size;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Math\Random;
use Magento\MediaStorage\Helper\File\Storage\Database as FileStorageDb;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * Files on return messages (hmCreateReturn, hmAddReturnMessage, hmEscalateReturn), stored the way the
 * website stores them.
 *
 * The website uploads each file first (Vnecoms\RMA\Controller\Customer\Upload) into
 * pub/media/rma/tmp/request, then posts their names with the form; saving the message or the
 * escalation moves them into pub/media/rma/request (Vnecoms\RMA\Model\FileUploader::moveFileFromTmp,
 * from Request::_createMessageObject and VendorsRMA Request::saveEscalateObject) and stores the names,
 * comma-separated, in the row's attachment column, which every page links from.
 *
 * The app sends the files with the mutation instead. They are checked (AttachmentRules), written where
 * the upload controller writes them - under a name no file has in either folder, where the website's
 * upload only avoids the temporary folder and its move overwrites a namesake in rma/request - and handed
 * to Vnecoms' own save, which moves them. A save that fails removes them again.
 */
class Attachments
{
    /** Where the website's upload controller puts a file until its message is saved. */
    public const TMP_DIR = 'rma/tmp/request';

    /** Where Vnecoms moves it, and the pages link it from (Message::getAttachmentUrls). */
    public const DIR = 'rma/request';

    public function __construct(
        private readonly RmaConfig $rmaConfig,
        private readonly NotProtectedExtension $protectedExtension,
        private readonly Size $fileSize,
        private readonly Filesystem $filesystem,
        private readonly FileStorageDb $fileStorageDb,
        private readonly Random $random,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The rules for the current store view's website (rma/general/allow_file_extension is a website
     * setting).
     */
    public function rules(): AttachmentRules
    {
        return new AttachmentRules(
            AttachmentRules::extensions(
                (string) $this->rmaConfig->allowFileExtension(),
                fn (string $extension): bool => !$this->protectedExtension->isValid($extension)
            ),
            AttachmentRules::maxBytes((int) $this->fileSize->getMaxFileSize())
        );
    }

    /**
     * @param mixed $inputs [HmReturnAttachmentInput] or null
     * @return array<int, array{name: string, extension: string, content: string}>
     * @throws GraphQlInputException
     */
    public function check(mixed $inputs): array
    {
        return $this->rules()->check($inputs);
    }

    /**
     * Write checked files into the upload controller's folder under free names, for Vnecoms' save to
     * move: the names go in the message's (or escalation's) "attachment", comma-separated.
     *
     * @param array<int, array{name: string, extension: string, content: string}> $files check()
     * @return string[] the names written, in order
     * @throws GraphQlInputException when a file can't be written (those written are removed)
     */
    public function stage(array $files): array
    {
        if (!$files) {
            return [];
        }
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $names = [];
        try {
            foreach ($files as $file) {
                $name = $this->freeName($media, $file['name']);
                $media->writeFile(self::TMP_DIR . '/' . $name, $file['content']);
                $names[] = $name;
                //  As the upload controller does (FileUploader::saveFileToTmpDir): a no-op unless media
                //  is kept in the database.
                $this->fileStorageDb->saveFile(self::TMP_DIR . '/' . $name);
            }
        } catch (\Throwable $e) {
            $this->logger->error('HubAppReturns: attachment not written: ' . $e->getMessage());
            $this->discard($names);
            throw new GraphQlInputException(__('We couldn\'t save the attachments. Please try again.'));
        }

        return $names;
    }

    /**
     * Remove staged files wherever a failed save left them: still waiting, or already moved.
     *
     * @param string[] $names stage()
     */
    public function discard(array $names): void
    {
        if (!$names) {
            return;
        }
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        foreach ($names as $name) {
            foreach ([self::TMP_DIR, self::DIR] as $dir) {
                $this->remove($media, $dir . '/' . $name);
            }
        }
    }

    /**
     * After a save: a file Vnecoms could not move out of the temporary folder was left off the message
     * (moveFileFromTmp logs and skips it, on the website too); it is removed.
     *
     * @param string[] $names stage()
     */
    public function sweep(array $names): void
    {
        if (!$names) {
            return;
        }
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        foreach ($names as $name) {
            if ($media->isExist(self::TMP_DIR . '/' . $name)) {
                $this->logger->warning('HubAppReturns: attachment ' . $name . ' was not moved and is left off the message');
                $this->remove($media, self::TMP_DIR . '/' . $name);
            }
        }
    }

    /**
     * The checked name with a random suffix, free in both folders: the public URL of a customer's
     * photo can't be guessed from its name, and no file of another return is overwritten.
     */
    private function freeName(WriteInterface $media, string $name): string
    {
        $info = pathinfo($name);
        $base = (string) ($info['filename'] ?? 'file');
        $extension = (string) ($info['extension'] ?? '');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = $base . '_' . $this->random->getRandomString(10, Random::CHARS_LOWERS . Random::CHARS_DIGITS)
                . ($extension !== '' ? '.' . $extension : '');
            if (!$media->isExist(self::TMP_DIR . '/' . $candidate) && !$media->isExist(self::DIR . '/' . $candidate)) {
                return $candidate;
            }
        }
        throw new \RuntimeException('no free name for ' . $name);
    }

    private function remove(WriteInterface $media, string $path): void
    {
        try {
            if ($media->isExist($path)) {
                $media->delete($path);
            }
            $this->fileStorageDb->deleteFile($path);
        } catch (\Throwable $e) {
            $this->logger->warning('HubAppReturns: attachment ' . $path . ' not removed: ' . $e->getMessage());
        }
    }
}
