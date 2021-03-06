<?php

namespace ImageConvert\Convert\Converters;

use ImageConvert\Convert\Converters\AbstractConverter;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInputException;
use ImageConvert\Convert\Exceptions\ConversionFailedException;

/**
 * Convert images to webp using gd extension.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class Gd extends AbstractConverter
{
    public function supportsLossless()
    {
        return false;
    }

    protected function getUnsupportedDefaultOptions()
    {
        return [
            'alpha-quality',
            'auto-filter',
            'encoding',
            'low-memory',
            'metadata',
            'method',
            'near-lossless',
            'preset',
            'sharp-yuv',
            'size-in-percentage',
        ];
    }

    private $errorMessageWhileCreating = '';
    private $errorNumberWhileCreating;

    /**
     * Check (general) operationality of Gd converter.
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met
     */
    public function checkOperationality()
    {
        if (!extension_loaded('gd')) {
            throw new SystemRequirementsNotMetException('Required Gd extension is not available.');
        }

        if (!function_exists('imagepalettetotruecolor')) {
            if (!self::functionsExist([
                'imagecreatetruecolor', 'imagealphablending', 'imagecolorallocatealpha',
                'imagefilledrectangle', 'imagecopy', 'imagedestroy', 'imagesx', 'imagesy'
            ])) {
                throw new SystemRequirementsNotMetException(
                    'Gd cannot convert palette color images to RGB. ' .
                    'Even though it converting RGB would work fine, ' .
                    'we refuse to do it. A partial working converter causes more trouble than ' .
                    'a non-working. To make this converter work, you need the imagepalettetotruecolor() ' .
                    'function to be enabled on your system.'
                );
            }
        }
    }

    /**
     * Check if converter supports converting between the two formats
     *
     * @param  string $sourceType  (last part of mime type, ie "jpeg")
     * @param  string $destinationType
     * @return void
     * @throws SystemRequirementsNotMetException  if Gd has been compiled without support for image type
     */
    public function checkConvertability($sourceType, $destinationType)
    {
        foreach ([$sourceType, $destinationType] as $imageType) {
            if (!in_array($imageType, ['png', 'jpeg', 'webp', 'avif', 'gif'])) {
                // how about: bmp, xbm, xpm, wbmp ?
                throw new SystemRequirementsNotMetException(
                    'Gd does not support ' . strtoupper($imageType) . ' images.'
                );
            }
        }

        if (!function_exists('imagecreatefrom' . $sourceType)) {
            throw new SystemRequirementsNotMetException(
                'Gd has been compiled without ' . strtoupper($sourceType) .
                    ' support and can therefore not convert this ' . strtoupper($sourceType) . ' image.'
            );
        }

        if ($destinationType == 'avif') {
            if (version_compare(phpversion(), '8.1', '<')) {
                throw new SystemRequirementsNotMetException(
                    'avif requires PHP 8.1 for Gd'
                );
            }
        }

        if (!function_exists('image' . $destinationType)) {
            throw new SystemRequirementsNotMetException(
                'Gd has been compiled without ' . strtoupper($destinationType) . ' support.'
            );
        }

        // PS: could alternatively use gd_info()
    }

    /**
     * Find out if all functions exists.
     *
     * @return boolean
     */
    private static function functionsExist($functionNamesArr)
    {
        foreach ($functionNamesArr as $functionName) {
            if (!function_exists($functionName)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Try to convert image pallette to true color on older systems that does not have imagepalettetotruecolor().
     *
     * The aim is to function as imagepalettetotruecolor, but for older systems.
     * So, if the image is already rgb, nothing will be done, and true will be returned
     * PS: Got the workaround here: https://secure.php.net/manual/en/function.imagepalettetotruecolor.php
     *
     * @param  resource|\GdImage  $image
     * @return boolean  TRUE if the convertion was complete, or if the source image already is a true color image,
     *          otherwise FALSE is returned.
     */
    private function makeTrueColorUsingWorkaround(&$image)
    {
        //return $this->makeTrueColor($image);
        /*
        if (function_exists('imageistruecolor') && imageistruecolor($image)) {
            return true;
        }*/
        if (self::functionsExist(['imagecreatetruecolor', 'imagealphablending', 'imagecolorallocatealpha',
                'imagefilledrectangle', 'imagecopy', 'imagedestroy', 'imagesx', 'imagesy'])) {
            $dst = imagecreatetruecolor(imagesx($image), imagesy($image));

            if ($dst === false) {
                return false;
            }

            $success = false;

            //prevent blending with default black
            if (imagealphablending($dst, false) !== false) {
                //change the RGB values if you need, but leave alpha at 127
                $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);

                if ($transparent !== false) {
                    //simpler than flood fill
                    if (imagefilledrectangle($dst, 0, 0, imagesx($image), imagesy($image), $transparent) !== false) {
                        //restore default blending
                        if (imagealphablending($dst, true) !== false) {
                            if (imagecopy($dst, $image, 0, 0, 0, 0, imagesx($image), imagesy($image)) !== false) {
                                $success = true;
                            }
                        };
                    }
                }
            }
            if ($success) {
                imagedestroy($image);
                $image = $dst;
            } else {
                imagedestroy($dst);
            }
            return $success;
        } else {
            // The necessary methods for converting color palette are not avalaible
            return false;
        }
    }

    /**
     * Try to convert image pallette to true color.
     *
     * Try to convert image pallette to true color. If imagepalettetotruecolor() exists, that is used (available from
     * PHP >= 5.5.0). Otherwise using workaround found on the net.
     *
     * @param  resource|\GdImage  $image
     * @return boolean  TRUE if the convertion was complete, or if the source image already is a true color image,
     *          otherwise FALSE is returned.
     */
    private function makeTrueColor(&$image)
    {
        if (function_exists('imagepalettetotruecolor')) {
            return imagepalettetotruecolor($image);
        } else {
            $this->logLn(
                'imagepalettetotruecolor() is not available on this system - using custom implementation instead'
            );
            return $this->makeTrueColorUsingWorkaround($image);
        }
    }

    /**
     * Create Gd image resource from source
     *
     * @throws  InvalidInputException  if mime type is unsupported or could not be detected
     * @throws  ConversionFailedException  if imagecreatefrompng or imagecreatefromjpeg fails
     * @return  resource|\GdImage  $image  The created image
     */
    private function createImageResource()
    {
        $mimeType = $this->getMimeTypeOfSource();

        switch ($this->sourceType) {
            case 'png':
                $image = imagecreatefrompng($this->source);
                if ($image === false) {
                    throw new ConversionFailedException(
                        'Gd failed when trying to load/create image (imagecreatefrompng() failed)'
                    );
                }
                return $image;

            case 'jpeg':
                $image = imagecreatefromjpeg($this->source);
                if ($image === false) {
                    throw new ConversionFailedException(
                        'Gd failed when trying to load/create image (imagecreatefromjpeg() failed)'
                    );
                }
                return $image;

            case 'gif':
                $image = imagecreatefromgif($this->source);
                if ($image === false) {
                    throw new ConversionFailedException(
                        'Gd failed when trying to load/create image (imagecreatefromgif() failed)'
                    );
                }
                return $image;

            case 'webp':
                $image = imagecreatefromwebp($this->source);
                if ($image === false) {
                    throw new ConversionFailedException(
                        'Gd failed when trying to load/create image (imagecreatefromwebp() failed)'
                    );
                }
                return $image;

            case 'avif':
                $image = imagecreatefromavif($this->source);
                if ($image === false) {
                    throw new ConversionFailedException(
                        'Gd failed when trying to load/create image (imagecreatefromavif() failed)'
                    );
                }
                return $image;
        }

        throw new InvalidInputException('Unsupported mime type');
    }

    /**
     * Try to make image resource true color if it is not already.
     *
     * @param  resource|\GdImage  $image  The image to work on
     * @return boolean|null   True if it is now true color. False if it is NOT true color. null, if we cannot tell
     */
    protected function tryToMakeTrueColorIfNot(&$image)
    {
        $whatIsItNow = null;
        $mustMakeTrueColor = false;
        if (function_exists('imageistruecolor')) {
            if (imageistruecolor($image)) {
                $this->logLn('image is true color');
                return true;
            } else {
                $this->logLn('image is not true color');
                $mustMakeTrueColor = true;
                $whatIsItNow = false;
            }
        } else {
            $this->logLn('It can not be determined if image is true color');
            $mustMakeTrueColor = true;
        }

        if ($mustMakeTrueColor) {
            $this->logLn('converting color palette to true color');
            $success = $this->makeTrueColor($image);
            if ($success) {
                return true;
            } else {
                $this->logLn(
                    'FAILED converting color palette to true color. '
                );
            }
        }
        return $whatIsItNow;
    }

    /**
     *
     * @param  resource|\GdImage  $image
     * @return boolean  true if alpha blending was set successfully, false otherwise
     */
    protected function trySettingAlphaBlending($image)
    {
        if (function_exists('imagealphablending')) {
            // TODO: Should we set second parameter to false instead?
            // As here: https://www.texelate.co.uk/blog/retaining-png-transparency-with-php-gd
            // (PS: I have backed up some local changes - to Gd.php, which includes changing that param
            // to false. But I didn't finish up back then and now I forgot, so need to retest before
            // changing anything...
            if (!imagealphablending($image, true)) {
                $this->logLn('Warning: imagealphablending() failed');
                return false;
            }
        } else {
            $this->logLn(
                'Warning: imagealphablending() is not available on your system.' .
                ' Converting PNGs with transparency might fail on some systems'
            );
            return false;
        }

        if (function_exists('imagesavealpha')) {
            if (!imagesavealpha($image, true)) {
                $this->logLn('Warning: imagesavealpha() failed');
                return false;
            }
        } else {
            $this->logLn(
                'Warning: imagesavealpha() is not available on your system. ' .
                'Converting PNGs with transparency might fail on some systems'
            );
            return false;
        }
        return true;
    }

    protected function errorHandlerWhileConverting($errno, $errstr, $errfile, $errline)
    {
        $this->errorNumberWhileCreating = $errno;
        $this->errorMessageWhileCreating = $errstr . ' in ' . $errfile . ', line ' . $errline .
            ', PHP ' . PHP_VERSION . ' (' . PHP_OS . ')';
        //return false;
    }

    /**
     *
     * @param  resource|\GdImage  $image
     * @return void
     */
    protected function destroyAndRemove($image)
    {
        imagedestroy($image);
        if (file_exists($this->destination)) {
            unlink($this->destination);
        }
    }

    /**
     *
     * @param  resource|\GdImage  $image
     * @return void
     */
    protected function tryConverting($image)
    {

        // Danger zone!
        //    Using output buffering to generate image.
        //    In this zone, Do NOT do anything that might produce unwanted output
        //    Do NOT call $this->logLn
        // --------------------------------- (start of danger zone)

        $addedZeroPadding = false;
        set_error_handler(array($this, "errorHandlerWhileConverting"));

        // This line may trigger log, so we need to do it BEFORE ob_start() !
        $q = $this->getCalculatedQuality();

        ob_start();

        // Adding this try/catch is perhaps not neccessary.
        // I'm not certain that the error handler takes care of Throwable errors.
        // and - sorry - was to lazy to find out right now. So for now: better safe than sorry. #320
        $error = null;
        $success = false;

        try {
            switch ($this->destinationType) {
                case 'png':
                    $success = imagepng($image, null, $this->options['compression']);
                    break;
                case 'jpeg':
                    $success = imagejpeg($image, null, $q);
                    break;
                case 'gif':
                    $success = imagegif($image);
                    break;
                case 'avif':
                    // quality is optional, and ranges from 0 (worst quality, smaller file) to 100 (best quality, larger file).
                    // If -1 is provided, the default value 30 is used.

                    // https://php.watch/versions/8.1/gd-avif#imageavif

                    // We do not use auto quality (yet). It would involve mapping between jpeg quality and avif quality
                    $quality = $this->options['quality'];

                    // speed is optional, and ranges from 0 (slow, smaller file) to 10 (fast, larger file).
                    //    If -1 is provided, the default value 6 is used.
                    $speed = -1;

                    $success = imageavif($image, null, $quality, $speed);
                    break;
                case 'webp':
                    // Beware: This call can throw FATAL on windows (cannot be catched)
                    // This for example happens on palette images
                    $success = imagewebp($image, null, $q);
                    break;
            }
        } catch (\Exception $e) {
            $error = $e;
        } catch (\Throwable $e) {
            $error = $e;
        }
        if (!is_null($error)) {
            restore_error_handler();
            ob_end_clean();
            $this->destroyAndRemove($image);
            throw $error;
        }
        if (!$success) {
            $this->destroyAndRemove($image);
            ob_end_clean();
            restore_error_handler();
            throw new ConversionFailedException(
                'Failed creating image. Call to image' . $this->destinationType . ' failed.',
                $this->errorMessageWhileCreating
            );
        }

        // The following hack solves an `imagewebp` bug
        // See https://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files
        if ($this->destinationType == 'webp') {
            if (ob_get_length() % 2 == 1) {
                echo "\0";
                $addedZeroPadding = true;
            }
        }
        $output = ob_get_clean();
        restore_error_handler();

        if ($output == '') {
            $this->destroyAndRemove($image);
            throw new ConversionFailedException(
                'Gd failed: imagewebp() returned empty string'
            );
        }

        // --------------------------------- (end of danger zone).


        if ($this->errorMessageWhileCreating != '') {
            switch ($this->errorNumberWhileCreating) {
                case E_WARNING:
                    $this->logLn('An warning was produced during conversion: ' . $this->errorMessageWhileCreating);
                    break;
                case E_NOTICE:
                    $this->logLn('An notice was produced during conversion: ' . $this->errorMessageWhileCreating);
                    break;
                default:
                    $this->destroyAndRemove($image);
                    throw new ConversionFailedException(
                        'An error was produced during conversion',
                        $this->errorMessageWhileCreating
                    );
                    //break;
            }
        }

        if ($addedZeroPadding) {
            $this->logLn(
                'Fixing corrupt webp by adding a zero byte ' .
                '(older versions of Gd had a bug, but this hack fixes it)'
            );
        }

        $success = file_put_contents($this->destination, $output);

        if (!$success) {
            $this->destroyAndRemove($image);
            throw new ConversionFailedException(
                'Gd failed when trying to save the image. Check file permissions!'
            );
        }

        /*
        Previous code was much simpler, but on a system, the hack was not activated (a file with uneven number of bytes
        was created). This is puzzeling. And the old code did not provide any insights.
        Also, perhaps having two subsequent writes to the same file could perhaps cause a problem.
        In the new code, there is only one write.
        However, a bad thing about the new code is that the entire webp file is read into memory. This might cause
        memory overflow with big files.
        Perhaps we should check the filesize of the original and only use the new code when it is smaller than
        memory limit set in PHP by a certain factor.
        Or perhaps only use the new code on older versions of Gd
        https://wordpress.org/support/topic/images-not-seen-on-chrome/#post-11390284

        Here is the old code:

        $success = imagewebp($image, $this->destination, $this->getCalculatedQuality());

        if (!$success) {
            throw new ConversionFailedException(
                'Gd failed when trying to save the image as webp (call to imagewebp() failed). ' .
                'It probably failed writing file. Check file permissions!'
            );
        }


        // This hack solves an `imagewebp` bug
        // See https://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files
        if (filesize($this->destination) % 2 == 1) {
            file_put_contents($this->destination, "\0", FILE_APPEND);
        }
        */
    }

    // Although this method is public, do not call directly.
    // You should rather call the static convert() function, defined in AbstractConverter, which
    // takes care of preparing stuff before calling doConvert, and validating after.
    protected function doActualConvert()
    {
        $versionString = gd_info()["GD Version"];
        $this->logLn('GD Version: ' . $versionString);

        // Btw: Check out processWebp here:
        // https://github.com/Intervention/image/blob/master/src/Intervention/Image/Gd/Encoder.php

        // Create image resource
        $image = $this->createImageResource();

        // Try to convert color palette if it is not true color
        $isItTrueColorNow = $this->tryToMakeTrueColorIfNot($image);
        if ($isItTrueColorNow === false) {
            // our tests shows that converting palette fails on all systems,
            throw new ConversionFailedException(
                'Cannot convert image because it is a palette image and the palette image cannot ' .
                'be converted to RGB (which is required). To convert to RGB, we would need ' .
                'imagepalettetotruecolor(), which is not available on your system. ' .
                'Our workaround does not have the neccasary functions for converting to RGB either.'
            );
        }
        if (is_null($isItTrueColorNow)) {
            $isWindows = preg_match('/^win/i', PHP_OS);
            $isMacDarwin = preg_match('/^darwin/i', PHP_OS); // actually no problem in PHP 7.4 and 8.0
            if ($isWindows || $isMacDarwin) {
                throw new ConversionFailedException(
                    'Cannot convert image because it appears to be a palette image and the palette image ' .
                    'cannot be converted to RGB, as you do not have imagepalettetotruecolor() enabled. ' .
                    'Converting palette on ' .
                    ($isWindows ? 'Windows causes FATAL error' : 'Mac causes halt') .
                    'So we abort now'
                );
            }
        }


        if (($this->sourceType == 'png') && ($this->destinationType == 'webp')) {

            // Try to set alpha blending
            $this->trySettingAlphaBlending($image);

            if (function_exists('version_compare')) {
                if (version_compare($versionString, "2.1.1", "<=")) {
                    $this->logLn(
                        'BEWARE: Your version of Gd looses the alpha chanel when converting to webp.' .
                        'You should upgrade Gd, use another converter or stop converting PNGs. ' .
                        'See: https://github.com/rosell-dk/webp-convert/issues/238'
                    );
                } elseif (version_compare($versionString, "2.2.4", "<=")) {
                    $this->logLn(
                        'BEWARE: Older versions of Gd looses the alpha chanel when converting to webp.' .
                        'We have not tested if transparency fails on your (rather old) version of Gd. ' .
                        'Please let us know. ' .
                        'See: https://github.com/rosell-dk/webp-convert/issues/238'
                    );
                }
            }

        }

        // Try to convert it to webp
        $this->tryConverting($image);

        // End of story
        imagedestroy($image);
    }
}
