<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\File\Size;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Math\Random;
use Magento\MediaStorage\Helper\File\Storage\Database as FileStorageDb;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use MagentoEgypt\HubAppReturns\Model\Rma\Attachments;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * Files on return messages are staged where the website's upload puts them (pub/media/rma/tmp/request),
 * under names no file has there or in rma/request, for Vnecoms' save to move; a failed save removes them.
 */
final class AttachmentsTest extends TestCase
{
    /** @var WriteInterface&MockObject */
    private WriteInterface $media;

    /** @var FileStorageDb&MockObject */
    private FileStorageDb $storageDb;

    /** @var array<string, string> path => content written */
    private array $written = [];

    /** @var string[] paths that exist */
    private array $existing = [];

    /** @var string[] paths deleted */
    private array $deleted = [];

    protected function setUp(): void
    {
        $this->media = $this->createMock(WriteInterface::class);
        $this->media->method('isExist')->willReturnCallback(
            fn (string $path): bool => in_array($path, $this->existing, true) || isset($this->written[$path])
        );
        $this->media->method('writeFile')->willReturnCallback(function (string $path, string $content): int {
            $this->written[$path] = $content;

            return strlen($content);
        });
        $this->media->method('delete')->willReturnCallback(function (string $path): bool {
            $this->deleted[] = $path;
            unset($this->written[$path]);
            $this->existing = array_values(array_diff($this->existing, [$path]));

            return true;
        });
        $this->storageDb = $this->createMock(FileStorageDb::class);
    }

    public function testTheRulesAreTheWebsitesUploadSettings(): void
    {
        $rules = $this->attachments()->rules();

        self::assertSame(['jpg', 'jpeg', 'png', 'pdf'], $rules->extensions);
        self::assertSame(8388608, $rules->maxBytes);
        self::assertSame(5, $rules->maxFiles);
    }

    public function testFilesAreStagedWhereTheUploadPutsThemUnderFreeNames(): void
    {
        //  The first suffix drawn is taken in rma/request: another is drawn.
        $this->existing = ['rma/request/crack_aaaaaaaaaa.png'];
        $this->storageDb->expects(self::exactly(2))->method('saveFile');

        $names = $this->attachments(['aaaaaaaaaa', 'bbbbbbbbbb', 'cccccccccc'])->stage([
            ['name' => 'crack.png', 'extension' => 'png', 'content' => 'PNG'],
            ['name' => 'box.jpg', 'extension' => 'jpg', 'content' => 'JPG'],
        ]);

        self::assertSame(['crack_bbbbbbbbbb.png', 'box_cccccccccc.jpg'], $names);
        self::assertSame(
            ['rma/tmp/request/crack_bbbbbbbbbb.png' => 'PNG', 'rma/tmp/request/box_cccccccccc.jpg' => 'JPG'],
            $this->written
        );
        self::assertSame([], $this->attachments()->stage([]));
    }

    public function testAFileThatCannotBeWrittenLeavesNoneBehind(): void
    {
        $calls = 0;
        $this->media = $this->createMock(WriteInterface::class);
        $this->media->method('isExist')->willReturnCallback(fn (string $p): bool => isset($this->written[$p]));
        $this->media->method('writeFile')->willReturnCallback(function (string $path, string $content) use (&$calls) {
            if (++$calls === 2) {
                throw new \RuntimeException('disk full');
            }
            $this->written[$path] = $content;

            return strlen($content);
        });
        $this->media->method('delete')->willReturnCallback(function (string $path): bool {
            $this->deleted[] = $path;
            unset($this->written[$path]);

            return true;
        });

        try {
            $this->attachments(['aaaaaaaaaa', 'bbbbbbbbbb'])->stage([
                ['name' => 'a.png', 'extension' => 'png', 'content' => 'A'],
                ['name' => 'b.png', 'extension' => 'png', 'content' => 'B'],
            ]);
            self::fail('A failed write was not reported.');
        } catch (GraphQlInputException $e) {
            self::assertSame('We couldn\'t save the attachments. Please try again.', $e->getMessage());
        }
        self::assertSame([], $this->written);
        self::assertContains('rma/tmp/request/a_aaaaaaaaaa.png', $this->deleted);
    }

    public function testAFailedSaveRemovesTheFilesWhereverTheyGot(): void
    {
        //  One still waiting, one already moved by Vnecoms before the save failed.
        $this->existing = ['rma/tmp/request/a_1.png', 'rma/request/b_2.png'];

        $this->attachments()->discard(['a_1.png', 'b_2.png']);

        self::assertSame(['rma/tmp/request/a_1.png', 'rma/request/b_2.png'], $this->deleted);
        self::assertSame([], $this->existing);
    }

    public function testAfterASaveOnlyAFileVnecomsDidNotMoveIsRemoved(): void
    {
        $this->existing = ['rma/tmp/request/stuck_1.png', 'rma/request/moved_2.png'];

        $this->attachments()->sweep(['stuck_1.png', 'moved_2.png']);

        self::assertSame(['rma/tmp/request/stuck_1.png'], $this->deleted);
        self::assertSame(['rma/request/moved_2.png'], $this->existing);
    }

    /**
     * @param string[] $suffixes the random suffixes drawn, in order
     */
    private function attachments(array $suffixes = ['aaaaaaaaaa']): Attachments
    {
        $config = $this->createMock(RmaConfig::class);
        $config->method('allowFileExtension')->willReturn('jpg,jpeg,png,pdf,php');
        $protected = $this->createMock(NotProtectedExtension::class);
        $protected->method('isValid')->willReturnCallback(static fn (string $e): bool => $e !== 'php');
        $size = $this->createMock(Size::class);
        $size->method('getMaxFileSize')->willReturn(8388608);
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($this->media);
        $random = $this->createMock(Random::class);
        $random->method('getRandomString')->willReturnOnConsecutiveCalls(...$suffixes);

        return new Attachments(
            $config,
            $protected,
            $size,
            $filesystem,
            $this->storageDb,
            $random,
            $this->createMock(LoggerInterface::class)
        );
    }
}
