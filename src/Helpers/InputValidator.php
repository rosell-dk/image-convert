<?php

namespace ImageConvert\Helpers;

use ImageConvert\Helpers\MimeType;
use ImageConvert\Helpers\PathChecker;
use ImageConvert\Exceptions\InvalidInputException;
use ImageConvert\Exceptions\InvalidInput\InvalidImageTypeException;

/**
 * Functions for sanitizing.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.6
 */
class InputValidator
{

    private static $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
    ];

    /**
     * Check mimetype and if file path is ok and exists
     *
     * @return void
     */
    public static function checkMimeType($filePath, $allowedMimeTypes = null)
    {
        if (is_null($allowedMimeTypes)) {
            $allowedMimeTypes = self::$allowedMimeTypes;
        }
        // the following also tests that file path is ok and file exists
        $fileMimeType = MimeType::getMimeTypeDetectionResult($filePath);

        if (is_null($fileMimeType)) {
            throw new InvalidImageTypeException('Image type could not be detected');
        } elseif ($fileMimeType === false) {
            throw new InvalidImageTypeException('File seems not to be an image.');
        } elseif (!in_array($fileMimeType, $allowedMimeTypes)) {
            throw new InvalidImageTypeException('Unsupported mime type: ' . $fileMimeType);
        }
    }

    public static function checkSource($source)
    {
        PathChecker::checkSourcePath($source);
        self::checkMimeType($source);
    }

    public static function checkDestination($destination)
    {
        PathChecker::checkDestinationPath($destination);
    }

    public static function checkSourceAndDestination($source, $destination)
    {
        self::checkSource($source);
        self::checkDestination($destination);
    }
}
