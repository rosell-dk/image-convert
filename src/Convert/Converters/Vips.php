<?php

namespace ImageConvert\Convert\Converters;

use ImageConvert\Convert\Converters\AbstractConverter;
use ImageConvert\Convert\Converters\ConverterTraits\EncodingAutoTrait;
use ImageConvert\Convert\Exceptions\ConversionFailedException;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Options\BooleanOption;
use ImageConvert\Options\IntegerOption;

//require '/home/rosell/.composer/vendor/autoload.php';

/**
 * Convert images to webp using Vips extension.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class Vips extends AbstractConverter
{
    use EncodingAutoTrait;

    protected function getUnsupportedDefaultOptions()
    {
        return [
            'auto-filter',
            'size-in-percentage',
        ];
    }

    /**
    *  Get the options unique for this converter
     *
     *  @return  array  Array of options
     */
    public function getUniqueOptions($imageType)
    {
        $ssOption = new BooleanOption('smart-subsample', false);
        $ssOption->markDeprecated();
        return [
            $ssOption
        ];
    }

    /**
     * Check operationality of Vips converter.
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met
     */
    public function checkOperationality()
    {
        if (!extension_loaded('vips')) {
            throw new SystemRequirementsNotMetException('Required Vips extension is not available.');
        }

        if (!function_exists('vips_image_new_from_file')) {
            throw new SystemRequirementsNotMetException(
                'Vips extension seems to be installed, however something is not right: ' .
                'the function "vips_image_new_from_file" is not available.'
            );
        }

        if (!function_exists('vips_call')) {
            throw new SystemRequirementsNotMetException(
                'Vips extension seems to be installed, however something is not right: ' .
                'the function "vips_call" is not available.'
            );
        }

        if (!function_exists('vips_error_buffer')) {
            throw new SystemRequirementsNotMetException(
                'Vips extension seems to be installed, however something is not right: ' .
                'the function "vips_error_buffer" is not available.'
            );
        }
    }

    /**
     * Vips load and save functions have an image type in its name
     * Some use same name for the type as we do (ie "webpload" and "jpegload")
     * Others differ. avif files are for example loaded with "heifload".
     */
    private function getVipsImageTypeName($imageType)
    {
        if (in_array($imageType, ['png', 'jpeg', 'webp', 'tiff', 'svg', 'gif'])) {
            return $imageType;
        }
        switch ($imageType) {
            case 'avif':
                return 'heif';
        }
        return null;
    }

    private function getVipsLoadOrSaveFunctionName($loadOrSave = true)
    {
        $imageType = ($loadOrSave ? $this->sourceType : $this->destinationType);
        $functionName = $this->getVipsImageTypeName($imageType);
        if ($functionName == null) {
            return null;
        }
        $functionName .= ($loadOrSave ? 'load' : 'save');
        return $functionName;
    }


    private function checkCanLoadOrSave($loadOrSave = true)
    {
        $imageType = ($loadOrSave ? $this->sourceType : $this->destinationType);

        $functionName = $this->getVipsLoadOrSaveFunctionName($loadOrSave);

        if (is_null($functionName)) {
            throw new SystemRequirementsNotMetException(
                $sourceType . ' is not currently supported.'
            );
        }

        if ($functionName == 'pngload') {
            // pngload errors critically when called improperly, so we cannot test if pngload is there
            // However, lets check 'pngsave' instead - it pngsave is there, so is pngload, probably
            $functionName = 'pngsave';
        }
        if ($functionName == 'heifload') {
            $functionName = 'heifsave';
        }

        $result = vips_call($functionName, null);
        if ($result == -1) {
            $message = vips_error_buffer();
            if (strpos($message, 'VipsOperation: class "' . $functionName . '" not found') === 0) {
                throw new SystemRequirementsNotMetException(
                    'Vips has not been compiled with ' . $imageType . ' support.'
                );
            }
        }
    }

    private function checkCanLoad()
    {
        $this->checkCanLoadOrSave(true);
    }

    private function checkCanSave()
    {
        $this->checkCanLoadOrSave(false);
    }

    /**
     * Check if converter supports converting between the two formats
     *
     * @param  string $sourceType  (last part of mime type, ie "jpeg")
     * @param  string $destinationType
     * @return void
     * @throws SystemRequirementsNotMetException  if Vips does not support image type
     */
    public function checkConvertability($sourceType, $destinationType)
    {
        // https://www.libvips.org/API/current/func-list.html

        // PS: To find php functions defined by vips, look here:
        // https://github.com/libvips/php-vips-ext/blob/master/vips.c  (search for "PHP_FUNCTION(")
        // vips_foreign_find_load()

        // check names of functions here: https://www.libvips.org/API/current/VipsForeignSave.html#vips-ppmload

        $this->checkCanLoad();
        $this->checkCanSave();

        if (function_exists('vips_version')) {
            // (added in 1.0.8: https://github.com/libvips/php-vips-ext/blob/master/ChangeLog)
            $this->logLn('vipslib version: ' . vips_version());
        }
        $this->logLn('vips extension version: ' . phpversion('vips'));
    }

    /**
     * Create vips image resource from source file
     *
     * @throws  ConversionFailedException  if image resource cannot be created
     * @return  resource  vips image resource
     */
    private function createImageResource()
    {
        // We are currently using vips_image_new_from_file(), but we could consider
        // calling vips_jpegload / vips_pngload instead
        $result = /** @scrutinizer ignore-call */ vips_image_new_from_file($this->source, []);
        if ($result === -1) {
            /*throw new ConversionFailedException(
                'Failed creating new vips image from file: ' . $this->source
            );*/
            $message = /** @scrutinizer ignore-call */ vips_error_buffer();
            throw new ConversionFailedException($message);
        }

        if (!is_array($result)) {
            throw new ConversionFailedException(
                'vips_image_new_from_file did not return an array, which we expected'
            );
        }

        if (count($result) != 1) {
            throw new ConversionFailedException(
                'vips_image_new_from_file did not return an array of length 1 as we expected ' .
                '- length was: ' . count($result)
            );
        }

        $im = array_shift($result);
        return $im;
    }

    /*
     * Save, using vips extension.
     *
     * Tries to save image resource, using the supplied options.
     * Vips fails when a parameter is not supported, but we detect this and unset that parameter and try again
     * (recursively call itself until there is no more of these kind of errors).
     */

    private function saveImage($im, $options, $possiblyUnsupported)
    {
        vips_error_buffer(); // clear error buffer
        $saveFunctionName = $this->getVipsLoadOrSaveFunctionName(false);
        $result = vips_call($saveFunctionName, $im, $this->destination, $options);

        //trigger_error('test-warning', E_USER_WARNING);
        if ($result === -1) {
            $message = vips_error_buffer();

            $nameOfPropertyNotFound = '';
            if (preg_match("#no property named .(.*).#", $message, $matches)) {
                $nameOfPropertyNotFound = $matches[1];
            } elseif (preg_match("#(.*)\\sunsupported$#", $message, $matches)) {
                // Actually, I am not quite sure if this ever happens.
                // I got a "near_lossless unsupported" error message in a build, but perhaps it rather a warning
                if (isset($possiblyUnsupported[$matches[1]])) {
                    $nameOfPropertyNotFound = $matches[1];
                }
            }

            if ($nameOfPropertyNotFound != '') {
                $msg = 'Note: Your version of vipslib does not support the "' .
                    $nameOfPropertyNotFound . '" property';

                if (isset($possiblyUnsupported[$nameOfPropertyNotFound])) {
                    $msg .= ' ' . $possiblyUnsupported[$nameOfPropertyNotFound];
                }
                $msg .= '. The option is ignored.';

                $this->logLn($msg, 'bold');

                unset($options[$nameOfPropertyNotFound]);
                $this->saveImage($im, $options, $possiblyUnsupported);
            } else {
                throw new ConversionFailedException($message);
            }
        }
    }

    /**
     * Create parameters for webpsave
     *
     * @return  array  the parameters as an array
     */
    private function createParamsForVipsWebPSave()
    {
        // webpsave options are described here:
        // https://libvips.github.io/libvips/API/current/VipsForeignSave.html#vips-webpsave
        // near_lossless option is described here: https://github.com/libvips/libvips/pull/430
        // you can also get the list of supported option by executing: `vips webpsave`

        // NOTE: When a new option becomes available, we MUST remember to add
        //       it to the array of possibly unsupported options in webpsave() !
        $options = [
            "Q" => $this->getCalculatedQuality(),
            'lossless' => ($this->options['encoding'] == 'lossless'),
            'strip' => $this->options['metadata'] == 'none',
        ];

        // Only set the following options if they differ from the default of vipslib
        // This ensures we do not get warning if that property isn't supported
        if ($this->options['smart-subsample'] !== false) {
            // PS: The smart-subsample option is now deprecated, as it turned out
            // it was corresponding to the "sharp-yuv" option (see #280)
            $options['smart_subsample'] = $this->options['smart-subsample'];
            $this->logLn(
                '*Note: the "smart-subsample" option is now deprecated. It turned out it corresponded to ' .
                'the general option "sharp-yuv". You should use "sharp-yuv" instead.*'
            );
        }
        if ($this->options['sharp-yuv'] !== false) {
            $options['smart_subsample'] = $this->options['sharp-yuv'];
        }

        if ($this->options['alpha-quality'] !== 100) {
            $options['alpha_q'] = $this->options['alpha-quality'];
        }

        if (!is_null($this->options['preset']) && ($this->options['preset'] != 'none')) {
            // preset. 0:default, 1:picture, 2:photo, 3:drawing, 4:icon, 5:text, 6:last
            $options['preset'] = array_search(
                $this->options['preset'],
                ['default', 'picture', 'photo', 'drawing', 'icon', 'text']
            );
        }
        if ($this->options['near-lossless'] !== 100) {
            if ($this->options['encoding'] == 'lossless') {
                // We only let near_lossless have effect when encoding is set to lossless
                // otherwise encoding=auto would not work as expected
                // Available in https://github.com/libvips/libvips/pull/430, merged 1 may 2016
                // seems it corresponds to release 8.4.2
                $options['near_lossless'] = true;

                // In Vips, the near-lossless value is controlled by Q.
                // this differs from how it is done in cwebp, where it is an integer.
                // We have chosen same option syntax as cwebp
                $options['Q'] = $this->options['near-lossless'];
            }
        }
        if ($this->options['method'] !== 4) {
            $options['reduction_effort'] = $this->options['method'];
        }

        $possiblyUnsupported = [
            'lossless' => '',
            'alpha_q' => '(it was introduced in libvips 8.4)',
            'near_lossless' => '(it was introduced in libvips 8.4)',
            'smart_subsample' => '(its the vips equalent to the "sharp-yuv" option. It was introduced in libvips 8.4)',
            'reduction_effort' => '(its the vips equalent to the "method" option. It was introduced in libvips 8.8.0)',
            'preset' => '(it was introduced in libvips 8.4)'
        ];

        return [$options, $possiblyUnsupported];
    }

    /**
     * Create parameters for save
     *
     * @return  array  the parameters as an array
     */
    private function createParamsForVipsSave()
    {
        // TODO:
        // Find out which versions the individual options was introduced.
        // I can dig into old docs here:
        // 7.40.11: https://www.manpagez.com/html/libvips/libvips-7.40.11/VipsForeignSave.php
        // 8.0.2:   https://www.manpagez.com/html/libvips/libvips-8.0.2/VipsForeignSave.php
        // 8.1.0:   https://www.manpagez.com/html/libvips/libvips-8.1.0/libvips-VipsForeignSave.php
        // 8.2.1:   https://www.manpagez.com/html/libvips/libvips-8.2.1/libvips-VipsForeignSave.php#vips-pngsave
        // 8.3.0:   https://www.manpagez.com/html/libvips/libvips-8.3.0/libvips-VipsForeignSave.php
        // 8.4.1:   https://www.manpagez.com/html/libvips/libvips-8.4.1/VipsForeignSave.php
        // 8.5.6:   https://www.manpagez.com/html/libvips/libvips-8.5.6/VipsForeignSave.php
        // 8.6.0:   https://www.manpagez.com/html/libvips/libvips-8.6.0/VipsForeignSave.php
        // 8.6.5:   https://www.manpagez.com/html/libvips/libvips-8.6.5/VipsForeignSave.php#vips-pngsave
        // 8.7.0:   https://www.manpagez.com/html/libvips/libvips-8.7.0/VipsForeignSave.php
        // 8.8.2:   https://www.manpagez.com/html/libvips/libvips-8.8.2/VipsForeignSave.php
        // 8.10.2:  https://www.manpagez.com/html/libvips/libvips-8.10.2/VipsForeignSave.php
        // 8.11.2:  https://www.manpagez.com/html/libvips/libvips-8.11.2/VipsForeignSave.php#vips-pngsave
        // current: https://www.libvips.org/API/current/VipsForeignSave.html

        switch ($this->destinationType) {
            case 'webp':
                return $this->createParamsForVipsWebPSave();
            case 'png':
                // for all possibilities, run 'vips pngsave'
                // we should at least support:
                // - strip
                // - interlace
                // - Q (0-100)
                return [
                    [
                        'compression' => $this->options['compression'],     // Compression factor (0-9). Ddefault: 6
                        'interlace' => $this->options['interlace'],
                        // 'profile' =>
                        // 'filter' =>
                        'palette' => false,
                        'Q' => 100,      // Quantisation quality (0-100). Default: 100
                        'dither' => $this->options['dither'],   // 0-1. Default: 1
                        //'bitdepth' => 8,  // Beware: fails with 0, which is supposed to be default
                        'strip' => false,  // Default: false
                        // 'background' =>
                        // 'page-height' =>
                        'effort' => 9,   // quantisation CPU effort. 0 is fastest, 9 is slowest. Default: 4
                    ],
                    [
                        'compression' => '',    // 7.40.11
                        'interlace' => '',      // 7.40.11
                        'profile' => '',        // 7.40.11
                        'filter' => '',         // 8.0.2
                        'palette' => '',        // 8.7.0
                        'colors' => '',         // 8.7.0
                        'Q' => '',              // 8.7.0
                        'dither' => '',         // 8.7.0
                        'bitdepth' => '',       // 8.10.2
                        'strip' => '',
                        'effort' => '',         // 8.12.0
                    ],
                ];
            case 'avif':
                //https://www.libvips.org/API/current/VipsForeignSave.html#vips-heifsave
                return [
                    [
                        'compression' => 'av1',  // av1 for avif
                        'Q' => 85,
                        'lossless' => false,
                        'speed' => 5,  // CPU effort (0-8). Default: 5
                        'effort' => 9,   // 0 is fastest, 9 is slowest. Default: 4
                    ],
                    []
                ];
        }
        return [[],[]];
    }


    /**
     * Convert, using vips extension.
     *
     * Tries to create image resource and save it as webp using the calculated options.
     * PS: The Vips "webpsave" call fails when a parameter is not supported, but our webpsave() method
     * detect this and unset that parameter and try again (repeat until success).
     *
     * @throws  ConversionFailedException  if conversion fails.
     */
    protected function doActualConvert()
    {
/*
        $im = \Jcupitt\Vips\Image::newFromFile($this->source);
        //$im->writeToFile(__DIR__ . '/images/small-vips.webp', ["Q" => 10]);

        $im->webpsave($this->destination, [
            "Q" => 80,
            //'near_lossless' => true
        ]);
        return;*/

        $im = $this->createImageResource();

        list($params, $possiblyUnsupported) = $this->createParamsForVipsSave();
        $this->logLn('vips params: ' . print_r($params, true));

        $this->saveImage($im, $params, $possiblyUnsupported);
    }
}
