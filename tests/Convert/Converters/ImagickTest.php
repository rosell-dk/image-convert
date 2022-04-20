<?php

namespace ImageConvert\Tests\Convert\Converters;

use ImageConvert\Convert\Converters\Imagick;
use ImageConvert\Loggers\BufferLogger;

use ImageMimeTypeGuesser\ImageMimeTypeGuesser;;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass ImageConvert\Convert\Converters\Imagick
 * @covers ImageConvert\Convert\Converters\Imagick
 */
class ImagickTest extends TestCase
{

    public static $imageDir = __DIR__ . '/../../images/';

    public static function getImageFolder()
    {
        return realpath(__DIR__ . '/../../images');
    }

    public static function getImagePath($image)
    {
        return self::getImageFolder() . '/' . $image;
    }

    public function testConvert()
    {
        ConverterTestHelper::runAllConvertTests($this, 'Imagick');
    }

    public function testConvertPng2Jpg()
    {
        $source = self::getImagePath('test.png');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        Imagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));

        //echo $bufferLogger->getText("\n");
    }

    public function testConvertJpg2PNG()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.png');

        $bufferLogger = new BufferLogger();
        Imagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/png', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertJpg2Avif()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.avif');

        $bufferLogger = new BufferLogger();
        Imagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/avif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertAvif2Jpeg()
    {
        $source = self::getImagePath('avif-test.avif');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        Imagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    /**
     * @coversNothing
     */
    public function testQueryFormats()
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped(
              'The imagick extension is not available.'
            );
            return;
        }
        //if (!class_exists('\\Imagick')) {}

        $im = new \Imagick();

        $this->assertEquals(1, count($im->queryFormats('JPEG')));
        $this->assertGreaterThan(2, count($im->queryFormats('*')));
        $this->assertGreaterThan(2, count($im->queryFormats()));
        $this->assertEquals(count($im->queryFormats('*')), count($im->queryFormats()));
    }

    /**
     * @coversNothing
     */
    public function testThatImagickFunctionsUsedDoesNotThrow()
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped(
              'The imagick extension is not available.'
            );
            return;
        }
        $im = new \Imagick(self::$imageDir . '/test.jpg');
        $im->setImageFormat('JPEG');
        $im->stripImage();
        $im->setImageCompressionQuality(100);
        $imageBlob = $im->getImageBlob();

        $this->addToAssertionCount(1);
    }
}
