<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Which files a return message may carry (HmReturnAttachmentInput), by the website's upload rules.
 *
 * On the website every file goes through Vnecoms\RMA\Controller\Customer\Upload before the form is
 * posted: Vnecoms\RMA\Model\FileUploader over Magento\MediaStorage\Model\File\Uploader, so
 *
 *  - types: the extensions of rma/general/allow_file_extension (comma-separated; by default txt, jpg,
 *    jpeg, png, gif, pdf, zip, rar, csv, doc, docx), alphanumeric only and never one of the store's
 *    protected extensions (general/file/protected_extensions: php, phtml, html, svg ...);
 *  - content: an image must open as one (Magento\MediaStorage\Model\File\Validator\Image); here an
 *    image extension must also carry that kind of image, and the declared MIME type must fit;
 *  - size: whatever PHP accepts for an upload (upload_max_filesize and post_max_size), since neither
 *    Vnecoms nor the uploader widget sets a limit; in the app at most MAX_BYTES a file;
 *  - count: none on the website, where each file is a request of its own; the app sends them all in
 *    one request, so at most MAX_FILES a message;
 *  - name: Magento's cleaning (Uploader::getCorrectFileName: characters other than letters, digits,
 *    _ - . become _), then AttachmentStore makes it unique.
 */
final class AttachmentRules
{
    /** Files one message may carry. */
    public const MAX_FILES = 5;

    /** Largest file the app may send, whatever PHP accepts: 10 MB. */
    public const MAX_BYTES = 10485760;

    /** The stored name keeps at most this much of the name sent (AttachmentStore adds a suffix). */
    private const MAX_BASE_LENGTH = 100;

    /** Image extensions: the content must be that image. */
    private const IMAGE_TYPES = [
        'jpg' => IMAGETYPE_JPEG,
        'jpeg' => IMAGETYPE_JPEG,
        'jpe' => IMAGETYPE_JPEG,
        'png' => IMAGETYPE_PNG,
        'gif' => IMAGETYPE_GIF,
        'bmp' => IMAGETYPE_BMP,
        'webp' => IMAGETYPE_WEBP,
    ];

    /** The MIME types a file of a known extension may be declared as. */
    private const MIME_TYPES = [
        'jpg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
        'jpe' => ['image/jpeg', 'image/jpg', 'image/pjpeg'],
        'png' => ['image/png', 'image/x-png'],
        'gif' => ['image/gif'],
        'bmp' => ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values', 'application/vnd.ms-excel'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'multipart/x-zip'],
        'rar' => ['application/vnd.rar', 'application/x-rar-compressed', 'application/x-rar'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];

    /**
     * @param string[] $extensions allowed extensions, lower case (extensions())
     * @param int $maxBytes largest file (maxBytes())
     */
    public function __construct(
        public readonly array $extensions,
        public readonly int $maxBytes,
        public readonly int $maxFiles = self::MAX_FILES
    ) {
    }

    /**
     * The allowed extensions as the website's upload controller reads the setting (split on commas),
     * less those Magento's uploader refuses: not alphanumeric, or protected.
     *
     * @param callable(string): bool $isProtected
     * @return string[] lower case, in the setting's order
     */
    public static function extensions(string $configured, callable $isProtected): array
    {
        $out = [];
        foreach (explode(',', $configured) as $extension) {
            $extension = strtolower(trim($extension));
            if ($extension === '' || preg_match('/[^a-z0-9]/', $extension) || $isProtected($extension)) {
                continue;
            }
            if (!in_array($extension, $out, true)) {
                $out[] = $extension;
            }
        }

        return $out;
    }

    /**
     * The largest file: PHP's upload limit (Magento\Framework\File\Size::getMaxFileSize()), at most
     * MAX_BYTES, and MAX_BYTES when PHP sets none.
     */
    public static function maxBytes(int $phpLimit): int
    {
        return $phpLimit > 0 ? min($phpLimit, self::MAX_BYTES) : self::MAX_BYTES;
    }

    /**
     * The files sent with a message, checked and decoded, in the order sent.
     *
     * @param mixed $inputs [HmReturnAttachmentInput] or null
     * @return array<int, array{name: string, extension: string, content: string}>
     * @throws GraphQlInputException
     */
    public function check(mixed $inputs): array
    {
        if ($inputs === null || $inputs === []) {
            return [];
        }
        if (!is_array($inputs)) {
            throw new GraphQlInputException(__('The attachments aren\'t valid.'));
        }
        if (count($inputs) > $this->maxFiles) {
            throw new GraphQlInputException(__('Attach at most %1 files.', $this->maxFiles));
        }
        $out = [];
        foreach ($inputs as $input) {
            $out[] = $this->checkOne(is_array($input) ? $input : []);
        }

        return $out;
    }

    /**
     * A file name cleaned as Magento's uploader cleans it (Uploader::getCorrectFileName), without any
     * folder, the extension in lower case and the rest cut to MAX_BASE_LENGTH.
     */
    public static function fileName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $slash = strrpos($name, '/');
        if ($slash !== false) {
            $name = substr($name, $slash + 1);
        }
        $name = (string) preg_replace('/[^a-z0-9_\-.]+/i', '_', ltrim($name, '.'));
        $info = pathinfo($name);
        $base = (string) ($info['filename'] ?? '');
        $extension = strtolower((string) ($info['extension'] ?? ''));
        if ($base === '' || preg_match('/^_+$/', $base)) {
            $base = 'file';
        }
        $base = substr($base, 0, self::MAX_BASE_LENGTH);

        return $extension === '' ? $base : $base . '.' . $extension;
    }

    /**
     * Whether a declared MIME type fits the extension: one of its known types (for a file that is no
     * image, application/octet-stream too); for an extension the store added, any well-formed type.
     */
    public static function mimeMatches(string $extension, string $mime): bool
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));
        if (preg_match('~^[a-z0-9][a-z0-9!#$&^_.+-]*/[a-z0-9][a-z0-9!#$&^_.+-]*$~', $mime) !== 1) {
            return false;
        }
        $known = self::MIME_TYPES[$extension] ?? null;
        if ($known === null || in_array($mime, $known, true)) {
            return true;
        }

        return !isset(self::IMAGE_TYPES[$extension]) && $mime === 'application/octet-stream';
    }

    /**
     * Whether the content is what the extension says: an image extension must hold that image, with
     * a size; any other file is kept as sent, as the website's uploader keeps it.
     */
    public static function contentMatches(string $extension, string $content): bool
    {
        $expected = self::IMAGE_TYPES[$extension] ?? null;
        if ($expected === null) {
            return true;
        }
        $info = @getimagesizefromstring($content);

        return is_array($info) && ($info[2] ?? null) === $expected && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0;
    }

    /**
     * @param array<string, mixed> $input HmReturnAttachmentInput
     * @return array{name: string, extension: string, content: string}
     * @throws GraphQlInputException
     */
    private function checkOne(array $input): array
    {
        $name = self::fileName((string) ($input['name'] ?? ''));
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $this->extensions, true)) {
            throw new GraphQlInputException(__(
                '"%1" can\'t be attached: only %2 files are allowed.',
                $name,
                implode(', ', $this->extensions)
            ));
        }
        if (!self::mimeMatches($extension, (string) ($input['mime_type'] ?? ''))) {
            throw new GraphQlInputException(__('"%1" isn\'t the type of file its name says.', $name));
        }
        $encoded = (string) ($input['content_base64'] ?? '');
        //  Four characters of base64 per three bytes, with room for line breaks: anything longer is
        //  over the limit before it is decoded.
        if (strlen($encoded) > (int) (ceil($this->maxBytes / 3) * 4 * 1.05) + 64) {
            throw new GraphQlInputException(__('"%1" is larger than %2 MB.', $name, self::megabytes($this->maxBytes)));
        }
        $content = base64_decode($encoded, true);
        if ($content === false || $content === '') {
            throw new GraphQlInputException(__('"%1" is empty or can\'t be read.', $name));
        }
        if (strlen($content) > $this->maxBytes) {
            throw new GraphQlInputException(__('"%1" is larger than %2 MB.', $name, self::megabytes($this->maxBytes)));
        }
        if (!self::contentMatches($extension, $content)) {
            throw new GraphQlInputException(__('"%1" isn\'t a valid %2 file.', $name, strtoupper($extension)));
        }

        return ['name' => $name, 'extension' => $extension, 'content' => $content];
    }

    /**
     * The limit in MB for a message: whole, else rounded down to a tenth (and at least 0.1), so a file
     * of the size shown always fits.
     */
    private static function megabytes(int $bytes): string
    {
        $megabytes = $bytes / 1048576;
        if (floor($megabytes) === $megabytes) {
            return (string) (int) $megabytes;
        }

        return rtrim(rtrim(number_format(max(0.1, floor($megabytes * 10) / 10), 1, '.', ''), '0'), '.');
    }
}
