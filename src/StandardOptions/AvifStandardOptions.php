<?php

namespace ImageConvert\StandardOptions;

use ImageConvert\Options\Options;
use ImageConvert\Options\OptionFactory;

/**
 * WebP Standard Options
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class AvifStandardOptions
{

    /**
     *  Get the "general" options (options that are standard in the meaning that they
     *  are generally available (unless specifically marked as unsupported by a given converter)
     *
     *  @param   string   $sourceImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *
     *  @return  array  Array of options
     */
    public static function getAvifStandardOptions($sourceImageType)
    {
        $isPng = ($sourceImageType == 'png');

        $introMd = 'https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/' .
            'converting/introduction-for-converting.md';

        return OptionFactory::createOptions([

            /*
            Options can be found here:
            - Squoosh: https://github.com/GoogleChromeLabs/squoosh/blob/dev/libsquoosh/src/codecs.ts (search for "avif:")
            - Vips: https://www.libvips.org/API/current/VipsForeignSave.html#vips-heifsave
            - cavif: https://github.com/kornelski/cavif-rs
            - avifenc:  type "avifenc", or see here: https://web.dev/compress-images-avif/
            - avifcli: https://github.com/lovell/avif-cli
            - gd: https://php.watch/versions/8.1/gd-avif#imageavif

            */
            /*
            "quality" or "cq-level" ?
            - Squoosh uses "cqLevel" (0-63), and allows separate alpha quality (cqAlphaLevel)
            - vips uses "Q"  (1-100) and has "lossless" option for lossless. Q:30 for avif is supposedly similar to Q:75 for jpeg
            - imagemagick uses "-quality" (0-100). Setting it to -1 results in lossless
                    https://stackoverflow.com/questions/55457916/how-to-use-format-specific-options-in-imagemagick
            - cavif uses "quality" (1-100, default: 80)
            - avifenc uses
            - avif-cli uses "quality" (1-100), default: 50
            - squoosh-cli
            - gd uses "quality" (from -1 to 100). -1 means "reuse libavif default". 0 is worst

            vips for some reason generates a little bit larger avif than imagemagick, but its around the same for all
            quality settings, so they probably have the same view of what quality means
            they both create significantly larger avif with quality 80 than webp with quality 80

            */

            ['quality', 'int', [
                'title' => 'Quality',
                'description' => 'Q 30 gives about the same quality as JPEG Q 75.',
                'default' => 30,
                'minimum' => 0,
                'maximum' => 100,
                'ui' => [
                    'component' => 'slider',
                    'advanced' => true,
                ]
            ]],
            /*
            "speed" or "effort" ?
            - Gd uses "speed"
            - Vips uses "effort", but used to use "speed" (it seems they are related (but opposite)
            - ImageMagick uses "speed"
            - Squoosh.app uses "effort (0-10)"
            - cavif uses "speed" (0-10)

            cavif writes this about speed:
            Encoding speed between 1 (best, but slowest) and 10 (fastest, but a blurry mess),
             the default value is 4. Speeds 1 and 2 are unbelievably slow, but make files ~3-5% smaller.
             Speeds 7 and above degrade compression significantly, and are not recommended.

            ['speed', 'int', [
                'title' => 'Speed',
                'description' =>
                    'ranges from 0 (slow, smaller file) to 9 (fast, larger file)',
                'default' => 4,
                'minimum' => 0,
                'maximum' => 9,
                'ui' => [
                    'component' => 'slider',
                    'advanced' => true,
                ]
            ]],
            */
        ]);
    }
}
