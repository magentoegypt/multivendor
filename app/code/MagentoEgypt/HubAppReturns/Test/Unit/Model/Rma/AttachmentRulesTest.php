<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MagentoEgypt\HubAppReturns\Model\Rma\AttachmentRules;
use PHPUnit\Framework\TestCase;

/**
 * The files a return message may carry: the website's upload rules (the RMA allowed extensions less
 * Magento's protected ones, images that open as images, Magento's file-name cleaning), the store's
 * upload limit capped at 10 MB, and at most 5 files a message.
 */
final class AttachmentRulesTest extends TestCase
{
    /** 1x1 images, as small as each format allows. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    private const JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/wAALCAABAAEBAREA/9k=';

    public function testTheAllowedExtensionsAreTheSettingLessWhatTheUploaderRefuses(): void
    {
        $protected = static fn (string $extension): bool => in_array($extension, ['php', 'svg', 'html'], true);

        self::assertSame(
            ['txt', 'jpg', 'jpeg', 'png', 'gif', 'pdf'],
            AttachmentRules::extensions(' txt,JPG ,jpeg,png,,gif,pdf,php,SVG,x-y,png', $protected)
        );
        self::assertSame([], AttachmentRules::extensions('', $protected));
    }

    public function testTheLargestFileIsTheStoresUploadLimitAtMostTenMegabytes(): void
    {
        self::assertSame(2097152, AttachmentRules::maxBytes(2097152));
        self::assertSame(AttachmentRules::MAX_BYTES, AttachmentRules::maxBytes(67108864));
        //  PHP sets no limit: the app's own.
        self::assertSame(AttachmentRules::MAX_BYTES, AttachmentRules::maxBytes(0));
        self::assertSame(AttachmentRules::MAX_BYTES, AttachmentRules::maxBytes(-1));
    }

    public function testNamesAreCleanedAsMagentosUploaderCleansThem(): void
    {
        self::assertSame('passwd.jpg', AttachmentRules::fileName('../../etc/passwd.jpg'));
        self::assertSame('IMG_0001.jpg', AttachmentRules::fileName('C:\\Photos\\IMG 0001.JPG'));
        self::assertSame('file.png', AttachmentRules::fileName('صورة.png'));
        self::assertSame('photo_1_.jpeg', AttachmentRules::fileName('photo "1".jpeg'));
        self::assertSame('htaccess', AttachmentRules::fileName('.htaccess'));
        self::assertSame(str_repeat('a', 100) . '.gif', AttachmentRules::fileName(str_repeat('a', 300) . '.gif'));
    }

    public function testPhotosAreDecodedInTheOrderSent(): void
    {
        $files = $this->rules()->check([
            $this->file('IMG_0001.JPG', 'image/jpeg', self::JPEG),
            $this->file('crack.png', 'image/png', self::PNG),
            $this->file('receipt.pdf', 'application/pdf', base64_encode('%PDF-1.4 receipt')),
        ]);

        self::assertSame(['IMG_0001.jpg', 'crack.png', 'receipt.pdf'], array_column($files, 'name'));
        self::assertSame(['jpg', 'png', 'pdf'], array_column($files, 'extension'));
        self::assertSame(base64_decode(self::PNG), $files[1]['content']);
        self::assertSame([], $this->rules()->check(null));
        self::assertSame([], $this->rules()->check([]));
    }

    public function testAtMostFiveFilesAMessage(): void
    {
        $six = array_fill(0, 6, $this->file('a.gif', 'image/gif', self::GIF));
        self::assertCount(5, $this->rules()->check(array_slice($six, 0, 5)));

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Attach at most 5 files.');
        $this->rules()->check($six);
    }

    public function testOnlyTheAllowedExtensions(): void
    {
        $this->assertRefused(
            $this->file('run.php', 'text/plain', base64_encode('<?php echo 1;')),
            '"run.php" can\'t be attached: only jpg, jpeg, png, gif, pdf files are allowed.'
        );
        $this->assertRefused(
            $this->file('photo', 'image/jpeg', self::JPEG),
            '"photo" can\'t be attached: only jpg, jpeg, png, gif, pdf files are allowed.'
        );
    }

    public function testTheDeclaredTypeMustFitTheExtension(): void
    {
        $this->assertRefused(
            $this->file('a.png', 'image/jpeg', self::PNG),
            '"a.png" isn\'t the type of file its name says.'
        );
        //  An image is never a generic download.
        $this->assertRefused(
            $this->file('a.jpg', 'application/octet-stream', self::JPEG),
            '"a.jpg" isn\'t the type of file its name says.'
        );
        //  Any other file may be.
        self::assertCount(1, $this->rules()->check([
            $this->file('a.pdf', 'application/octet-stream', base64_encode('%PDF-1.4')),
        ]));
        self::assertTrue(AttachmentRules::mimeMatches('jpg', 'IMAGE/JPEG; q=1'));
        self::assertFalse(AttachmentRules::mimeMatches('pdf', 'not a type'));
    }

    public function testAnImageExtensionMustHoldThatImage(): void
    {
        $this->assertRefused(
            $this->file('a.jpg', 'image/jpeg', self::PNG),
            '"a.jpg" isn\'t a valid JPG file.'
        );
        $this->assertRefused(
            $this->file('a.gif', 'image/gif', base64_encode('<script>alert(1)</script>')),
            '"a.gif" isn\'t a valid GIF file.'
        );
    }

    public function testTheContentMustBeBase64AndNotEmpty(): void
    {
        $this->assertRefused($this->file('a.png', 'image/png', 'iVBOR*w0K'), '"a.png" is empty or can\'t be read.');
        $this->assertRefused($this->file('a.png', 'image/png', ''), '"a.png" is empty or can\'t be read.');
        //  Line breaks, as some encoders add them, are fine.
        self::assertCount(1, $this->rules()->check([
            $this->file('a.png', 'image/png', chunk_split(self::PNG, 20, "\n")),
        ]));
    }

    public function testNoFileLargerThanTheLimit(): void
    {
        $rules = new AttachmentRules(['png', 'pdf'], 100);
        self::assertCount(1, $rules->check([$this->file('a.pdf', 'application/pdf', base64_encode(str_repeat('x', 100)))]));

        try {
            $rules->check([$this->file('a.pdf', 'application/pdf', base64_encode(str_repeat('x', 101)))]);
            self::fail('A file over the limit was accepted.');
        } catch (GraphQlInputException $e) {
            self::assertSame('"a.pdf" is larger than 0.1 MB.', $e->getMessage());
        }
        //  Far over: refused before it is decoded.
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('"b.pdf" is larger than 0.1 MB.');
        $rules->check([$this->file('b.pdf', 'application/pdf', str_repeat('QUFB', 1000))]);
    }

    public function testSomethingOtherThanAListIsRefused(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('The attachments aren\'t valid.');
        $this->rules()->check('photo.jpg');
    }

    private function rules(): AttachmentRules
    {
        return new AttachmentRules(['jpg', 'jpeg', 'png', 'gif', 'pdf'], AttachmentRules::MAX_BYTES);
    }

    /**
     * @param array<string, string> $file HmReturnAttachmentInput
     */
    private function assertRefused(array $file, string $message): void
    {
        try {
            $this->rules()->check([$file]);
            self::fail('Accepted: ' . $file['name']);
        } catch (GraphQlInputException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }

    /**
     * @return array<string, string> HmReturnAttachmentInput
     */
    private function file(string $name, string $mime, string $content): array
    {
        return ['name' => $name, 'mime_type' => $mime, 'content_base64' => $content];
    }
}
