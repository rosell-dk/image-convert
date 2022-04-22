<?php

namespace ImageConvert\Convert\Converters;

use ImageConvert\Convert\Converters\AbstractConverter;
use ImageConvert\Convert\Exceptions\ConversionFailedException;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Converters\ConverterTraits\EncodingAutoTrait;

//use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInput\TargetNotFoundException;

/**
 * Convert images to webp using Gmagick extension.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class Gmagick extends AbstractConverter
{
    use EncodingAutoTrait;

    protected function getUnsupportedDefaultOptions()
    {
        return [
            'near-lossless',
            'size-in-percentage',
        ];
    }

    /**
     * Check (general) operationality of Gmagick converter.
     *
     * Note:
     * It may be that Gd has been compiled without jpeg support or png support.
     * We do not check for this here, as the converter could still be used for the other.
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met
     */
    public function checkOperationality()
    {
        if (!extension_loaded('Gmagick')) {
            throw new SystemRequirementsNotMetException('Required Gmagick extension is not available.');
        }

        if (!class_exists('Gmagick')) {
            throw new SystemRequirementsNotMetException(
                'Gmagick is installed, but not correctly. The class Gmagick is not available'
            );
        }

        $im = new \Gmagick($this->source);

        if (!in_array('WEBP', $im->queryformats())) {
            throw new SystemRequirementsNotMetException('Gmagick was compiled without WebP support.');
        }
    }

    /**
     * Check if converter supports converting between the two formats
     *
     * @param  string $sourceType  (last part of mime type, ie "jpeg")
     * @param  string $destinationType
     * @return void
     * @throws SystemRequirementsNotMetException  if Gmagick does not support image type
     */
    public function checkConvertability($sourceType, $destinationType)
    {

        if ($destinationType != 'webp') {
            throw new SystemRequirementsNotMetException('the converter currently only supports converting to webp');
        }

        /*
        $im = new \Gmagick();
        $mimeType = $this->getMimeTypeOfSource();
        switch ($mimeType) {
            case 'image/png':
                if (!in_array('PNG', $im->queryFormats())) {
                    throw new SystemRequirementsNotMetException(
                        'Gmagick has been compiled without PNG support and can therefore not convert this PNG image.'
                    );
                }
                break;
            case 'image/jpeg':
                if (!in_array('JPEG', $im->queryFormats())) {
                    throw new SystemRequirementsNotMetException(
                        'Gmagick has been compiled without Jpeg support and can therefore not convert this Jpeg image.'
                    );
                }
                break;
        }*/
    }

    // Although this method is public, do not call directly.
    // You should rather call the static convert() function, defined in AbstractConverter, which
    // takes care of preparing stuff before calling doConvert, and validating after.
    protected function doActualConvert()
    {

        // PS: graphicsmagick options are documented here: (search for "webp:")
        // http://www.graphicsmagick.org/GraphicsMagick.html

        $options = $this->options;

        try {
            $im = new \Gmagick($this->source);
        } catch (\Exception $e) {
            throw new ConversionFailedException(
                'Failed creating Gmagick object of file',
                'Failed creating Gmagick object of file: "' . $this->source . '" - Gmagick threw an exception.',
                $e
            );
        }

        $im->setimageformat('WEBP');

        // setimageoption() has not always been there, so check first. #169
        if (method_exists($im, 'setimageoption')) {
            // Finally cracked setting webp options.
            // See #167
            // - and https://stackoverflow.com/questions/47294962/how-to-write-lossless-webp-files-with-perlmagick

            if (!is_null($options['preset'])) {
                if ($options['preset'] != 'none') {
                    $imageHint = $options['preset'];
                    switch ($imageHint) {
                        case 'drawing':
                        case 'icon':
                        case 'text':
                            $imageHint = 'graph';
                            $this->logLn(
                                'The "preset" value was mapped to "graph" because gmagick does not support "drawing",' .
                                ' "icon" and "text", but grouped these into one option: "graph".'
                            );
                    }
                    $im->setimageoption('webp', 'image-hint', $imageHint);
                }
            }
            $im->setimageoption('webp', 'method', $options['method']);
            $im->setimageoption('webp', 'lossless', $options['encoding'] == 'lossless' ? 'true' : 'false');
            $im->setimageoption('webp', 'alpha-quality', $options['alpha-quality']);

            if ($options['auto-filter'] === true) {
                $im->setimageoption('webp', 'auto-filter', 'true');
            }

            if ($options['sharp-yuv'] === true) {
                $im->setimageoption('webp', 'use-sharp-yuv', 'true');
            }
        }

        /*
        low-memory seems not to be supported:
        $im->setimageoption('webp', 'low-memory', $options['low-memory'] ? true : false);
        */

        if ($options['metadata'] == 'none') {
            // Strip metadata and profiles
            $im->stripImage();
        }

        // Ps: Imagick automatically uses same quality as source, when no quality is set
        // This feature is however not present in Gmagick
        // TODO: However, it might be possible after all - see #91
        $im->setcompressionquality($this->getCalculatedQuality());

        // We call getImageBlob().
        // That method is undocumented, but it is there!
        // - just like it is in imagick, as pointed out here:
        //   https://www.php.net/manual/ru/gmagick.readimageblob.php

        /** @scrutinizer ignore-call */
        $imageBlob = $im->getImageBlob();

        $success = @file_put_contents($this->destination, $imageBlob);

        if (!$success) {
            throw new ConversionFailedException('Failed writing file');
        }
    }
}
