<?php

namespace ImageConvert\Convert\Converters;

use ExecWithFallback\ExecWithFallback;
use LocateBinaries\LocateBinaries;

use ImageConvert\Convert\Converters\AbstractConverter;
use ImageConvert\Convert\Converters\ConverterTraits\ExecTrait;
use ImageConvert\Convert\Converters\ConverterTraits\EncodingAutoTrait;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Exceptions\ConversionFailedException;
use ImageConvert\Options\OptionFactory;

//use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInput\TargetNotFoundException;

/**
 * Convert images to webp by calling imagemagick binary.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class ImageMagick extends AbstractConverter
{
    use ExecTrait;
    use EncodingAutoTrait;

    private $version;   /* full version string - set in checkOperationality() */
    private $versionNumber;  /* extracted version number or "unknown" */

    protected function getUnsupportedDefaultOptions()
    {
        return [
            'size-in-percentage',
        ];
    }

    /**
     *  Get the options unique for this converter
     *
     * @return  array  Array of options
     */
    public function getUniqueOptions($imageType)
    {
        return OptionFactory::createOptions([
            self::niceOption(),
            ['try-common-system-paths', 'boolean', [
                'title' => 'Try locating ImageMagick in common system paths',
                'description' =>
                    'If set, the converter will look for a ImageMagick binaries residing in common system locations ' .
                    'such as "/usr/bin/convert". ' .
                    'If such exist, it is assumed that they are valid ImageMagick binaries. ',
                'default' => true,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true
                ]
            ]],
        ]);
    }

    // To futher improve this converter, I could check out:
    // https://github.com/Orbitale/ImageMagickPHP

    private function getPath()
    {
        if (defined('IMAGECONVERT_IMAGEMAGICK_PATH')) {
            return constant('IMAGECONVERT_IMAGEMAGICK_PATH');
        }
        if (!empty(getenv('IMAGECONVERT_IMAGEMAGICK_PATH'))) {
            return getenv('IMAGECONVERT_IMAGEMAGICK_PATH');
        }

        if ($this->options['try-common-system-paths']) {
            $binaries = LocateBinaries::locateInCommonSystemPaths('convert');
            if (!empty($binaries)) {
                return $binaries[0];
            }
        }

        return 'convert';
    }

    private function getVersion()
    {
        ExecWithFallback::exec($this->getPath() . ' -version 2>&1', $output, $returnCode);
        if (($returnCode == 0) && isset($output[0])) {
            return $output[0];
        } else {
            return 'unknown';
        }
    }

    public function isInstalled()
    {
        ExecWithFallback::exec($this->getPath() . ' -version 2>&1', $output, $returnCode);
        return ($returnCode == 0);
    }

    public function isDelegateInstalled($delegate)
    {
        ExecWithFallback::exec($this->getPath() . ' -list delegate 2>&1', $output, $returnCode);
        foreach ($output as $line) {
            if (preg_match('#' . $delegate . '\\s*=#i', $line)) {
                return true;
            }
        }

        // try other command
        ExecWithFallback::exec($this->getPath() . ' -list configure 2>&1', $output, $returnCode);
        foreach ($output as $line) {
            if (preg_match('#DELEGATE.*' . $delegate . '#i', $line)) {
                return true;
            }
        }

        // PS, convert -version does not output delegates on travis, so it is not reliable
        return false;
    }

    /**
     * Check (general) operationality of imagack converter executable
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met
     */
    public function checkOperationality()
    {
        $this->checkOperationalityExecTrait();

        if (!$this->isInstalled()) {
            throw new SystemRequirementsNotMetException(
                'imagemagick is not installed (cannot execute: "' . $this->getPath() . '")'
            );
        }

        $this->version = $this->getVersion();

        preg_match('#\d+\.\d+\.\d+[\d\.\-]+#', $this->version, $matches);
        $this->versionNumber = (isset($matches[0]) ? $matches[0] : 'unknown');
    }

    private function formatToLookFor($imageType)
    {
        switch ($imageType) {
            case 'jpeg':
            case 'png':
            case 'webp':
            case 'gif':
                return strtoupper($imageType) . '\\*\s+' . strtoupper($imageType);
                break;
            case 'avif':
                return 'AVIF* HEIC';
                break;
        }
        throw new SystemRequirementsNotMetException($imageType . ' format is not supported');
    }

    /**
     * Converters may override this for the purpose of performing checks on the concrete file.
     *
     * This can for example be used for rejecting big uploads in cloud converters or rejecting unsupported
     * image types.
     *
     * @param  string $sourceType  (last part of mime type, ie "jpeg")
     * @param  string $destinationType
     * @return void
     */
    public function checkConvertability($sourceType, $destinationType)
    {

        ExecWithFallback::exec($this->getPath() . ' -list format 2>&1', $output, $returnCode);

        // Check source
        $sourceSupported = false;
        $lookFor = self::formatToLookFor($sourceType) . '\s*r';
        foreach ($output as $line) {
            //$this->logLn($line);
            if (preg_match('#' . $lookFor . '#i', $line)) {
                $sourceSupported = true;
                break;
            }
        }
        if (!$sourceSupported) {
            throw new SystemRequirementsNotMetException($sourceType . ' format is not supported');
        }

        // Check destination
        $destinationSupported = false;
        $lookFor = self::formatToLookFor($destinationType) . '\s*.w';
        foreach ($output as $line) {
            //$this->logLn($line);
            if (preg_match('#' . $lookFor . '#i', $line)) {
                $destinationSupported = true;
                break;
            }
        }
        if (!$sourceSupported) {
            throw new SystemRequirementsNotMetException($sourceType . ' format is not supported');
        }

/*

        $needsDelegates = ['webp'];     // TODO: avif needs to check for 'heic' delegate

        // TODO:
        // This command seems to be what we are really looking for:
        // magick -list format | grep HEIC
        // Only question is: when was it introduced?

        if (in_array($sourceType, $needsDelegates)) {
            if (!$this->isDelegateInstalled($sourceType)) {
                throw new SystemRequirementsNotMetException($sourceType . ' delegate missing');
            }
        }

        if (in_array($destinationType, $needsDelegates)) {
            if (!$this->isDelegateInstalled($destinationType)) {
                throw new SystemRequirementsNotMetException($destinationType . ' delegate missing');
            }
        }
*/
        /*
        Commented out the following test, because I have AVIF working in 6.9.11-60.
        So, which version was it actually introduced?

        if (($sourceType == 'avif') || ($destinationType == 'avif')) {
            if (version_compare($this->versionNumber, '7.0.25', '<')) {
                throw new SystemRequirementsNotMetException(
                    'Your version of imagemagick does not support AVIF. You need at least imagemagick 7.0.25' .
                    ' (your version is: ' . $this->versionNumber . ')'
                );
            }
        }*/
    }

    /**
     * Prepend webp command line arguments to array
     *
     * @param  array commandArguments
     * @param  string $versionNumber. Ie "6.9.10-23"
     * @return void
     */
    private function prependWebPCommandLineArguments($commandArguments)
    {
        $options = $this->options;

        if (!is_null($options['preset'])) {
            // "image-hint" is at least available from 6.9.4-0 (I can't see further back)
            if ($options['preset'] != 'none') {
                $imageHint = $options['preset'];
                switch ($imageHint) {
                    case 'drawing':
                    case 'icon':
                    case 'text':
                        $imageHint = 'graph';
                        $this->logLn(
                            'The "preset" value was mapped to "graph" because imagemagick does not support "drawing",' .
                            ' "icon" and "text", but grouped these into one option: "graph".'
                        );
                }
                $commandArguments[] = '-define webp:image-hint=' . escapeshellarg($imageHint);
            }
        }

        if ($options['encoding'] == 'lossless') {
            // lossless is at least available from 6.9.4-0 (I can't see further back)
            $commandArguments[] = '-define webp:lossless=true';
        }

        if ($options['low-memory']) {
            // low-memory is at least available from 6.9.4-0 (I can't see further back)
            $commandArguments[] = '-define webp:low-memory=true';
        }

        if ($options['auto-filter'] === true) {
            // auto-filter is at least available from 6.9.4-0 (I can't see further back)
            $commandArguments[] = '-define webp:auto-filter=true';
        }

        if ($options['metadata'] == 'none') {
            $commandArguments[] = '-strip';
        }

        if ($options['alpha-quality'] !== 100) {
            // alpha-quality is at least available from 6.9.4-0 (I can't see further back)
            $commandArguments[] = '-define webp:alpha-quality=' . strval($options['alpha-quality']);
        }

        if ($options['sharp-yuv'] === true) {
            if (version_compare($this->versionNumber, '7.0.8-26', '>=')) {
                $commandArguments[] = '-define webp:use-sharp-yuv=true';
            } else {
                $this->logLn(
                    'Note: "sharp-yuv" option is not supported in your version of ImageMagick. ' .
                        'ImageMagic >= 7.0.8-26 is required',
                    'italic'
                );
            }
        }

        if ($options['near-lossless'] != 100) {
            if (version_compare($this->versionNumber, '7.0.10-54', '>=')) { // #299
                $commandArguments[] = '-define webp:near-lossless=' . escapeshellarg($options['near-lossless']);
            } else {
                $this->logLn(
                    'Note: "near-lossless" option is not supported in your version of ImageMagick. ' .
                        'ImageMagic >= 7.0.10-54 is required',
                    'italic'
                );
            }
        }

        // "method" is at least available from 6.9.4-0 (I can't see further back)
        $commandArguments[] = '-define webp:method=' . $options['method'];
        return $commandArguments;
    }

    /**
     * Build command line options
     *
     * @return string
     */
    private function createCommandLineOptions()
    {

        $commandArguments = [];

        $options = $this->options;

        switch ($this->destinationType) {
            case 'webp':
                // Available webp options for imagemagick are documented here:
                // - https://imagemagick.org/script/webp.php
                // - https://github.com/ImageMagick/ImageMagick/blob/main/coders/webp.c

                // PS: We should perhaps implement low-memory. Its already in cwebp, it
                // could perhaps be promoted to a general option

                if ($this->isQualityDetectionRequiredButFailing()) {
                    // quality:auto was specified, but could not be determined.
                    // we cannot apply the max-quality logic, but we can provide auto quality
                    // simply by not specifying the quality option.
                } else {
                    $commandArguments[] = '-quality ' . escapeshellarg($this->getCalculatedQuality());
                }
                $commandArguments = $this->prependWebPCommandLineArguments($commandArguments);
                break;

            case 'avif':
                // https://imagemagick.org/script/defines.php (search for "heic:")
                $commandArguments[] = '-quality ' . escapeshellarg($this->options['quality']);
        }

        $commandArguments[] = escapeshellarg($this->source);
        $commandArguments[] = escapeshellarg($this->destinationType . ':' . $this->destination);

        return implode(' ', $commandArguments);
    }

    protected function doActualConvert()
    {
        $this->logLn($this->version);
        $this->logLn('Extracted version number: ' . $this->versionNumber);

        $command = $this->getPath() . ' ' . $this->createCommandLineOptions() . ' 2>&1';

        $useNice = ($this->options['use-nice'] && $this->checkNiceSupport());
        if ($useNice) {
            $command = 'nice ' . $command;
        }
        $this->logLn('Executing command: ' . $command);
        ExecWithFallback::exec($command, $output, $returnCode);

        $this->logExecOutput($output);
        if ($returnCode == 0) {
            $this->logLn('success');
        } else {
            $this->logLn('return code: ' . $returnCode);
        }

        if ($returnCode == 127) {
            throw new SystemRequirementsNotMetException('imagemagick is not installed');
        }
        if ($returnCode != 0) {
            throw new SystemRequirementsNotMetException('The exec call failed');
        }
    }
}
