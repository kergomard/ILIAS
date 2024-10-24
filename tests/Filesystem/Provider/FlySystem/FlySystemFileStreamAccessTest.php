<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

namespace Filesystem\Provider\FlySystem;

\Hamcrest\Util::registerGlobalFunctions();

use ILIAS\Filesystem\Exception\FileAlreadyExistsException;
use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Provider\FlySystem\FlySystemFileStreamAccess;
use ILIAS\Filesystem\Stream\Streams;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

/**
 * @author                 Nicolas Schäfli <ns@studer-raimann.ch>
 * @author                 Fabian Schmid <fabian@sr.solutions>
 */
class FlySystemFileStreamAccessTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FilesystemInterface | MockInterface
     */
    private \Mockery\LegacyMockInterface $filesystemMock;
    private \ILIAS\Filesystem\Provider\FlySystem\FlySystemFileStreamAccess $subject;


    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystemMock = \Mockery::mock(FilesystemOperator::class);
        $this->subject = new FlySystemFileStreamAccess($this->filesystemMock);
    }

    /**
     * @Test
     * @small
     */
    public function testReadStreamWhichShouldSucceed(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = fopen('data://text/plain,' . $fileContent, $fileContent, 'r');

        $this->filesystemMock->shouldReceive('readStream')
            ->once()
            ->with($path)
            ->andReturn($stream);

        $wrappedStream = $this->subject->readStream($path);

        $this->assertSame($fileContent, $wrappedStream->getContents());
    }

    /**
     * @Test
     * @small
     */
    public function testReadStreamWithMissingFileWhichShouldFail(): void
    {
        $path = '/path/to/your/file';

        $this->filesystemMock->shouldReceive('readStream')
            ->once()
            ->with($path)
            ->andThrow(UnableToReadFile::class);

        $this->expectException(\ILIAS\Filesystem\Exception\FileNotFoundException::class);
        $this->expectExceptionMessage("File \"$path\" not found.");

        $this->subject->readStream($path);
    }

    /**
     * @Test
     * @small
     */
    public function testReadStreamWithGeneralFailureWhichShouldFail(): void
    {
        $path = '/path/to/your/file';

        $this->filesystemMock->shouldReceive('readStream')
            ->once()
            ->with($path)
            ->andReturn(false);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not open stream for file \"$path\"");

        $this->subject->readStream($path);
    }

    /**
     * @Test
     * @small
     */
    public function testWriteStreamWhichShouldSucceed(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock
            ->shouldReceive('fileExists')
            ->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('writeStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andReturn(true);

        $this->subject->writeStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testWriteStreamWithDetachedStreamWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);
        $stream->detach();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given stream must not be detached.');

        $this->subject->writeStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testWriteStreamWithExistingFileWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock
            ->shouldReceive('fileExists')
            ->once()
            ->andReturn(true);

        $this->expectException(FileAlreadyExistsException::class);
        $this->expectExceptionMessage("File \"$path\" already exists.");

        $this->subject->writeStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testWriteStreamWithFailingAdapterWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock
            ->shouldReceive('fileExists')
            ->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('writeStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andThrow(UnableToWriteFile::class);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not write stream to file \"$path\"");

        $this->subject->writeStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testPutStreamWhichShouldSucceed(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock->shouldReceive('putStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andReturn(true);

        $this->subject->putStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testPutStreamWithGeneralFailureWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock->shouldReceive('putStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andReturn(false);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not put stream content into \"$path\"");

        $this->subject->putStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testPutStreamWithDetachedStreamWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);
        $stream->detach();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given stream must not be detached.');

        $this->subject->putStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testUpdateStreamWhichShouldSucceed(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock->shouldReceive('writeStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andReturn(true);

        $this->subject->updateStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testUpdateStreamWithDetachedStreamWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);
        $stream->detach();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given stream must not be detached.');

        $this->subject->updateStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testUpdateStreamWithGeneralFailureWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock
            ->shouldReceive('writeStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andThrow(UnableToWriteFile::class);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Unable to update Stream in \"$path\".");

        $this->subject->updateStream($path, $stream);
    }

    /**
     * @Test
     * @small
     */
    public function testUpdateStreamWithMissingFileWhichShouldFail(): void
    {
        $path = '/path/to/your/file';
        $fileContent = 'Awesome file content';
        $stream = Streams::ofString($fileContent);

        $this->filesystemMock
            ->shouldReceive('writeStream')
            ->once()
            ->withArgs([$path, \resourceValue()])
            ->andThrow(UnableToWriteFile::class);

        $this->expectException(\ILIAS\Filesystem\Exception\FileNotFoundException::class);
        $this->expectExceptionMessage("Unable to update Stream in \"$path\".");

        $this->subject->updateStream($path, $stream);
    }
}
