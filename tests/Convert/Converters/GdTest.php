<?php
namespace ImageConvert\Tests\Convert\Converters;


use ImageConvert\Tests\Convert\Exposers\GdExposer;

use ImageConvert\ImageConvert;

use ImageConvert\Convert\Converters\Gd;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Exceptions\ConversionFailedException;
use ImageConvert\Loggers\BufferLogger;

use ImageMimeTypeGuesser\ImageMimeTypeGuesser;

use PHPUnit\Framework\TestCase;

class GdTest extends TestCase
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
        ConverterTestHelper::runAllConvertTests($this, 'Gd');
    }

    public function testGlobalConvert()
    {
        $source = self::getImagePath('test.png');
        $destination = self::getImagePath('test.png.avif');
        $bufferLogger = new BufferLogger();
        $result = ImageConvert::convert($source, $destination, [
            'converters' => ['cwebp', 'ewww', 'ffmpeg', 'gmagick', 'graphicsmagick', 'imagemagick', 'imagick', 'gd'],
            'quality' => 20,
        ], $bufferLogger);
        //echo $bufferLogger->getText("\n");
        $this->assertFalse(false);
    }

    public function testConvertPng2Jpg()
    {
        $source = self::getImagePath('test.png');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
    }

    public function testConvertJpg2PNG()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.png');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [
                'compression' => 1,
                'interlace' => true,
                'dither' => 1,
            ],
            $bufferLogger
        );
        $this->assertEquals('image/png', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertJpg2Webp()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.webp');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [
                'quality' => 80,
                'near-lossless' => 77,
                'method' => 2,
                'encoding' => 'lossless'
            ],
            $bufferLogger
        );
        $this->assertEquals('image/webp', ImageMimeTypeGuesser::detect($destination));
        $log = $bufferLogger->getText("\n");
        //echo $log;

    }

    /*public function testConvertJpg2GIF()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.gif');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/gif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }*/


    public function testConvertJpg2Avif()
    {
        // To install Gd with avif:
        // https://php.watch/versions/8.1/gd-avif#compile

        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.avif');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/avif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }
    /*

    public function testConvertAvif2Jpeg()
    {
        $source = self::getImagePath('avif-test.avif');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        Gd::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }*/

    private function createGd($src)
    {
        $source = self::getImagePath($src);
        $this->assertTrue(file_exists($source), 'source does not exist:' . $source);

        return new Gd($source, $source . '.webp');
    }

    private function createGdExposer($src)
    {
        $gd = $this->createGd($src);
        return new GdExposer($gd);
    }

    private static function resetPretending()
    {
        reset_pretending();
    }

    // pretend imagewebp is missing
    /*
    public function testNotOperational1()
    {
        global $pretend;

        $gd = $this->createGd('test.png');
        self::resetPretending();

        $pretend['functionsNotExisting'] = ['imagewebp'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $gd->checkOperationality();
    }
*/

    // pretend gd is not loaded
    public function testNotOperational2()
    {
        global $pretend;

        $gd = $this->createGd('test.png');
        self::resetPretending();

        $pretend['extensionsNotExisting'] = ['gd'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $gd->checkOperationality();
        $pretend['extensionsNotExisting'] = [];
    }

    // pretend imagecreatefrompng is missing
    public function testCheckConvertability1()
    {
        global $pretend;

        $gd = $this->createGd('test.png');
        self::resetPretending();

        $pretend['functionsNotExisting'] = ['imagecreatefrompng'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $gd->checkConvertability('png', 'webp');
        $pretend['functionsNotExisting'] = [];
    }

    // pretend imagecreatefrompng is working
    public function testCheckConvertability2()
    {
        global $pretend;

        $gd = $this->createGd('test.png');
        self::resetPretending();

        $pretend['functionsExisting'] = ['imagecreatefrompng'];
        $gd->checkConvertability('png', 'webp');
        $pretend['functionsExisting'] = [];
    }

    // pretend imagecreatefromjpeg is missing
    public function testCheckConvertability3()
    {
        global $pretend;

        $gd = $this->createGd('test.jpg');
        self::resetPretending();

        $pretend['functionsNotExisting'] = ['imagecreatefromjpeg'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $gd->checkConvertability('jpeg', 'webp');

        $pretend['functionsNotExisting'] = [];
    }

    public function testSource()
    {

        $source = self::getImagePath('test.png');
        $gd = new Gd($source, $source . '.webp');

        self::resetPretending();

        $gdExposer = new GdExposer($gd);

        $this->assertEquals($source, $gdExposer->getSource());
        $this->assertTrue(file_exists($source), 'source does not exist');
    }

    public function testCreateImageResource()
    {
        $gd = $this->createGd('test.png');
        self::resetPretending();

        $gdExposer = new GdExposer($gd);

        if (!$gdExposer->isOperating()) {
            //$this->assertTrue(false);
            return;
        }

        // It is operating and image should be ok.
        // - so it should be able to create image resource (or, for PHP 8, an \GdImage object)
        $image = $gdExposer->createImageResource();
        $isResourceOrObject = ((gettype($image) == 'resource') || (gettype($image) == 'object'));
        $this->assertTrue($isResourceOrObject, 'Expected createImageResource to return a resource or an object but got:' . gettype($image));

/*
        // Try the workaround method.
        $result = $gdExposer->makeTrueColorUsingWorkaround($image);

        // As the workaround is pretty sturdy, let us assert that it simply works.
        // It would be good to find out if it doesn't, anyway!
        $this->assertTrue($result);            */

        //$gdExposer->tryToMakeTrueColorIfNot($image);
        $this->assertTrue(imageistruecolor($image), 'image is not true color');

        $result = $gdExposer->trySettingAlphaBlending($image);
        $this->assertTrue($result, 'failed setting alpha blending');
    }

    public function testStuffOnNotTrueColor()
    {
        $gd = $this->createGd('not-true-color.png');
        self::resetPretending();

        $gdExposer = new GdExposer($gd);

        if (!$gdExposer->isOperating()) {
            return;
        }

        // It is operating and image should be ok.
        // - so it should be able to create image resource
        $image = $gdExposer->createImageResource();
        $isResourceOrObject = ((gettype($image) == 'resource') || (gettype($image) == 'object'));
        $this->assertTrue($isResourceOrObject, 'Expected createImageResource to return a resource or an object but got:' . gettype($image));

        $this->assertFalse(imageistruecolor($image), 'image is already true color');
        $gdExposer->tryToMakeTrueColorIfNot($image);
        $this->assertTrue(imageistruecolor($image), 'image is not true color after trying to make it');
        $result = $gdExposer->trySettingAlphaBlending($image);
        $this->assertTrue($result, 'failed setting alpha blending');

        // Test the workaround method.
        $gd = $this->createGd('not-true-color.png');
        $gdExposer = new GdExposer($gd);
        $image = $gdExposer->createImageResource();
        $this->assertFalse(imageistruecolor($image), 'image is already true color');

        //$image = imagecreatetruecolor(imagesx($image), imagesy($image));
        $result = $gdExposer->makeTrueColorUsingWorkaround($image);
        //$result = $gd->makeTrueColorUsingWorkaround($image);
        $this->assertTrue($result);
        $this->assertTrue(imageistruecolor($image), 'image is not true color after trying to make it (with workaround method)');
        $result = $gdExposer->trySettingAlphaBlending($image);
        $this->assertTrue($result, 'failed setting alpha blending');
    }

    public function testConvertFailure()
    {
        echo 'OS: ' . PHP_OS;
        $gdExposer = $this->createGdExposer('not-true-color.png');

        self::resetPretending();

        // The next requires imagewebp...
        if (!function_exists('imagewebp')) {
            return;
        }

        $image = $gdExposer->createImageResource();

        // This image is not true color.
        // Trying to convert it fails (empty string is generated)
        // Assert that I am right!

        // In most cases the following triggers a warning:
        // Warning: imagewebp(): Palette image not supported by webp in /var/www/wc/wc0/webp-convert/tests/Convert/Converters/GdTest.php on line 215
        //
        // However, in Windows-2022 (PHP 8), it throws A FATAL!
        // Error: PHP Fatal error:  Paletter image not supported by webp in D:\a\webp-convert\webp-convert\tests\Convert\Converters\GdTest.php on line 222
        //
        // And its worse and Mac (PHP 7.1 and 7.3, not 7.4 and 8.0)
        // It just halts execution - see ##322

        $isWindows = preg_match('/^win/i', PHP_OS);
        $isMacDarwin = preg_match('/^darwin/i', PHP_OS);

        if (!$isWindows && !$isMacDarwin) {
            ob_start();

            try {
                @imagewebp($image, null, 80);
            } catch (\Exception $e) {
            } catch (\Throwable $e) {
            }
            $output = ob_get_clean();

            // The failure results in no output:
            $this->assertEquals($output, '');

            // similary, we should get an exception when calling tryConverting ('Gd failed: imagewebp() returned empty string')
            //$this->expectException(ConversionFailedException::class);
            $gotExpectedException = false;
            try {
                $gdExposer->tryConverting($image);
            } catch (ConversionFailedException $e) {
                $gotExpectedException = true;
            }
            $this->assertTrue(
                $gotExpectedException,
                'did not get expected exception when converting palette image with Gd, ' .
                'bypassing the code that converts to true color'
            );

        }

          //$gdExposer->tryToMakeTrueColorIfNot($image);

          //$pretend['functionsNotExisting'] = ['imagewebp'];

    }
  /*
      public function testMakeTrueColorUsingWorkaround()
      {
          $gd = $this->createGd('test.png');
          self::resetPretending();

          $gdExposer = new GdExposer($gd);

          if (!$gdExposer->isOperating()) {
              return;
          }

      }*/
}

require_once('pretend.inc');
