<?php

namespace ImageConvert\Helpers;

use ImageMimeTypeGuesser\ImageMimeTypeGuesser;

use ImageConvert\Exceptions\InvalidInputException;
use ImageConvert\Exceptions\InvalidInput\TargetNotFoundException;

/**
 * Get MimeType, results cached by path.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.6
 */
class MimeType
{
    private static $cachedDetections = [];

    /**
     * Get mime type for image (best guess).
     *
     * It falls back to using file extension. If that fails too, false is returned
     *
     * @return  string|false|null mimetype (if it is an image, and type could be determined / guessed),
     *    false (if it is not an image type that the server knowns about)
     *    or null (if nothing can be determined)
     */
    public static function getMimeTypeDetectionResult($absFilePath)
    {
        PathChecker::checkAbsolutePathAndExists($absFilePath);

        if (isset(self::$cachedDetections[$absFilePath])) {
            return self::$cachedDetections[$absFilePath];
        }
        self::$cachedDetections[$absFilePath] = ImageMimeTypeGuesser::lenientGuess($absFilePath);
        return self::$cachedDetections[$absFilePath];
    }
}
