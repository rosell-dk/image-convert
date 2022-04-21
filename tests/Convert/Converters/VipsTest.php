<?php

namespace ImageConvert\Tests\Convert\Converters;

use ImageConvert\Convert\Converters\Vips;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Exceptions\ConversionFailedException;
use ImageConvert\Exceptions\InvalidInput\TargetNotFoundException;
use ImageConvert\Loggers\BufferLogger;

use ImageConvert\Tests\Convert\Exposers\VipsExposer;

use ImageMimeTypeGuesser\ImageMimeTypeGuesser;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass ImageConvert\Convert\Converters\Vips
 * @covers ImageConvert\Convert\Converters\Vips
 */
class VipsTest extends TestCase
{

    public function getImageFolder()
    {
        return realpath(__DIR__ . '/../../images');
    }

    public function getImagePath($image)
    {
        return $this->getImageFolder() . '/' . $image;
    }

    public function testConvertJpg2PNG()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.png');

        $bufferLogger = new BufferLogger();
        Vips::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/png', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertJpg2Webp()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.webp');

        $bufferLogger = new BufferLogger();
        Vips::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/webp', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    /*public function testConvertJpg2GIF()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.gif');

        $bufferLogger = new BufferLogger();
        Vips::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/gif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }*/


    public function testConvertJpg2Avif()
    {
        $source = self::getImagePath('test.jpg');
        $destination = self::getImagePath('test.jpg.avif');

        $bufferLogger = new BufferLogger();
        Vips::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/avif', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvertAvif2Jpeg()
    {
        $source = self::getImagePath('avif-test.avif');
        $destination = self::getImagePath('test.png.jpg');

        $bufferLogger = new BufferLogger();
        Vips::convert($source, $destination, [], $bufferLogger);
        $this->assertEquals('image/jpeg', ImageMimeTypeGuesser::detect($destination));
        //echo $bufferLogger->getText("\n");
    }

    public function testConvert()
    {
        $options = [];
        ConverterTestHelper::runAllConvertTests($this, 'Vips', $options);

        $options = [
            'smart-subsample' => true,
            'preset' => 'text',
        ];
        ConverterTestHelper::runAllConvertTests($this, 'Vips', $options);
    }


    private function createVips($src, $options = [])
    {
        $source = $this->getImagePath($src);
        $this->assertTrue(file_exists($source), 'source does not exist:' . $source);

        return new Vips($source, $source . '.webp', $options);
    }

    private function createVipsExposer($src, $options = [])
    {
        return new VipsExposer($this->createVips($src, $options));
    }

    private function isVipsOperational()
    {
        try {
            $vips = $this->createVips('test.png');
            $vips->checkOperationality();
            $vips->checkConvertability('png', 'jpg');
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function testCreateParamsForVipsWebPSave1()
    {
        $options = [
            'method' => 3,
            'encoding' => 'lossless',
            'smart-subsample' => true,  // note: deprecated
            'near-lossless' => 90,
            'preset' => 'picture',      // In vips, this has the constant: 1
        ];
        $vipsExposer = $this->createVipsExposer('test.png', $options);

        $vipsParams = $vipsExposer->createParamsForVipsWebPSave()[0];

        // Check some options that are straightforwardly copied

        $this->assertSame($options['method'], $vipsParams['reduction_effort']);
        $this->assertSame(true, $vipsParams['lossless']);
        $this->assertSame($options['smart-subsample'], $vipsParams['smart_subsample']);
        $this->assertSame(1, $vipsParams['preset']);

        // When near-lossless is set, the value should be copied to Q
        $this->assertSame($options['near-lossless'], $vipsParams['Q']);
    }

/*
    public function testCreateParamsForVipsWebPSave2()
    {
        $options = [
            'alpha-quality' => 100,
            'sharp-yuv' => true,
        ];
        $vipsExposer = $this->createVipsExposer('test.png', $options);

        $vipsParams = $vipsExposer->createParamsForVipsWebPSave();

        $this->assertSame($options['sharp-yuv'], isset($vipsParams['smart_subsample']));

        // Some options are only set if they differ from default
        $this->assertFalse(isset($vipsParams['alpha_q']));
    }*/


    public function testCreateImageResource1()
    {

        $source = $this->getImagePath('non-existing');

        // Next must fail with a TargetNotFoundException
        $this->expectException(TargetNotFoundException::class);

        $vips = new Vips($source, $source . '.webp', []);

        // Exit if vips is not operational
        if (!$this->isVipsOperational()) {
            return;
        }

        /*
        $vipsExposer = new VipsExposer($vips);

        // It must fail because it should not be able to create resource when file does not exist
        $this->expectException(ConversionFailedException::class);

        $vipsExposer->createImageResource();*/
    }

    public function testNotOperational1()
    {
        global $pretend;

        $vips = $this->createVips('test.png');
        reset_pretending();

        // pretend vips_image_new_from_file
        $pretend['functionsNotExisting'] = ['vips_image_new_from_file'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $vips->checkOperationality();
    }

    public function testNotOperational2()
    {
        global $pretend;

        $vips = $this->createVips('test.png');
        reset_pretending();

        // pretend vips_image_new_from_file
        $pretend['extensionsNotExisting'] = ['vips'];
        $this->expectException(SystemRequirementsNotMetException::class);
        $vips->checkOperationality();
    }

    /*
    Commented out because it is no good anymore, after the checkOperationality actually
    itself relies on the function_exists, because it now calls "vips_call" in order to
    detect if webp is supported

    public function testOperational1()
    {
        global $pretend;

        $vips = $this->createVips('test.png');
        reset_pretending();

        // pretend vips_image_new_from_file
        $pretend['functionsExisting'] = ['vips_image_new_from_file', 'vips_call', 'vips_error_buffer'];
        $pretend['extensionsExisting'] = ['vips'];
        $vips->checkOperationality();

        $this->addToAssertionCount(1);
    }
    */

    /**
     * @covers ::webpsave
     */
     /*
    public function testWebpsave()
    {
        reset_pretending();

        $vips = $this->createVips('test.png', []);
        $vipsExposer = new VipsExposer($vips);

        // Exit if vips is not operational
        if (!$this->isVipsOperational()) {
            $this->markTestSkipped('vips is not operational');
            return;
        }

        $im = $vipsExposer->createImageResource();
        $options = $vipsExposer->createParamsForVipsWebPSave();

        // Create non-existing vips option.
        // - The converter must be able to ignore this without failing
        $options['non-existing-vips-option'] = true;
        $vipsExposer->webpsave($im, $options);
    }*/

    /**
     * @covers ::createImageResource
     */
    public function testCreateImageResourceWhenFileNotFound()
    {
        //
        reset_pretending();

        $source = $this->getImagePath('i-do-not-exist.jpg');

        $this->assertFalse(file_exists($source));

        $options = [];

        // Next must fail with a TargetNotFoundException
        $this->expectException(TargetNotFoundException::class);

        $vips = new Vips($source, $source . '.webp', $options);

        // Exit if vips is not operational
        /*
        if (!$this->isVipsOperational()) {
            $this->markTestSkipped('vips is not operational');
            return;
        }*/

        /*
        $vipsExposer = new VipsExposer($vips);

        // this should fail!
        try {
            $im = $vipsExposer->createImageResource();
            $this->fail('exception was expected');
        } catch (ConversionFailedException $e) {
            $this->assertRegExp('#not found#', $e->getMessage());
        }*/

    }
/*
    public function testDoActualConvert()
    {

        $options = [
            'alpha-quality' => 100
        ];
        $vipsExposer = $this->createVipsExposer('test.png', $options);

        $vips = $this->createVips('not-existing.png');

        $this->addToAssertionCount(1);
    }*/
}

require_once('pretend.inc');
