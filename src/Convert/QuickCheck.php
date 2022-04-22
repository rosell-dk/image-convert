<?php

namespace ImageConvert\Convert;

use ExecWithFallback\ExecWithFallback;
use LocateBinaries\LocateBinaries;

/**
 * Check operationality
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class QuickCheck
{

    /**
     * @param $name   ie "FFMPEG" or "IMAGEMAGIC"
     * @param $binary   ie "convert"
     */
    private static function getBinaryPath($name, $binary)
    {
        if (defined('IMAGECONVERT_' . $name . '_PATH')) {
            return constant('IMAGECONVERT_' . $name . '_PATH');
        }
        if (!empty(getenv('IMAGECONVERT_' . $name . '_PATH'))) {
            return getenv('IMAGECONVERT_' . $name . '_PATH');
        }

        $binaries = LocateBinaries::locateInCommonSystemPaths($binary);
        if (!empty($binaries)) {
            return $binaries[0];
        }

        return $binary;
    }

    /**
     * Quick-check
     *
     * Quickly check if converter can be skipped
     *
     * @param  string  $converterId    Id of converter (ie "cwebp")
     *
     * @return string|void
     */
    public static function check($converterId, $sourceType, $destinationType)
    {
        if (in_array($converterId, ['cwebp'])) {
            return 'only used for converting to webp';
        }

        if (in_array($converterId, ['ewww', 'ffmpeg'])) {
            return 'currently only supports converting to webp';
        }

        if (in_array($converterId, ['ffmpeg', 'cwebp', 'imagemagick', 'gmagickbinary'])) {
            if (!ExecWithFallback::anyAvailable()) {
                return 'not operational - exec() is not enabled';
            }
        }

        switch ($converterId) {
            case 'ffmpeg':
                ExecWithFallback::exec(self::getBinaryPath('FFMPEG', 'ffmpeg') . ' -version 2>&1', $output, $returnCode);
                if ($returnCode != 0) {
                    return 'not installed';
                }
                break;
            case 'imagick':
                if (!extension_loaded('imagick')) {
                    return 'not installed';
                }
                break;
            case 'imagemagick':
                ExecWithFallback::exec(self::getBinaryPath('IMAGEMAGICK', 'convert') . ' -version 2>&1', $output, $returnCode);
                if ($returnCode != 0) {
                    return 'not installed';
                }
                break;
            case 'graphicsmagick':
                ExecWithFallback::exec(self::getBinaryPath('GRAPHICSMAGICK', 'gm') . ' -version 2>&1', $output, $returnCode);
                if ($returnCode != 0) {
                    return 'not installed';
                }
                break;
            case 'gmagick':
                if (!extension_loaded('Gmagick')) {
                    return 'not installed';
                }
                break;
        }
    }

}
