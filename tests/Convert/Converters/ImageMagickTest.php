<?php
/**
 * ImageConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace ImageConvert\Tests\Convert\Converters;

use ImageConvert\Convert\Converters\ImageMagick;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;
use ImageConvert\Loggers\BufferLogger;

use ImageMimeTypeGuesser\ImageMimeTypeGuesser;

use PHPUnit\Framework\TestCase;

class ImageMagickTest extends TestCase
{

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
        ConverterTestHelper::runAllConvertTests($this, 'ImageMagick');
    }

    public function testConvertPng2Jpg()
    {
        $source = self::getImagePath('test.png');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        ImageMagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
    }


    public function testConvertJpg2PNG()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.png');

        $bufferLogger = new BufferLogger();
        ImageMagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/png', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertJpg2Avif()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.avif');

        $bufferLogger = new BufferLogger();
        ImageMagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/avif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertAvif2Jpeg()
    {
        $source = self::getImagePath('avif-test.avif');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        ImageMagick::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    private static function tryThis($test, $source, $options)
    {
        $bufferLogger = new BufferLogger();

        try {
            ImageMagick::convert($source, $source . '.webp', $options, $bufferLogger);

            $test->addToAssertionCount(1);
        } catch (ConversionFailedException $e) {

            //$bufferLogger->getText()
            throw $e;
        } catch (ConverterNotOperationalException $e) {
            // (SystemRequirementsNotMetException is also a ConverterNotOperationalException)
            // this is ok.
            return;
        }
    }

    public function testWithNice() {
        $source = self::getImagePath('test.png');
        $options = [
            'use-nice' => true,
            'encoding' => 'lossless',
        ];
        self::tryThis($this, $source, $options);
    }

}
