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
class PNGStandardOptions
{

    /**
     *  Get the "general" options (options that are standard in the meaning that they
     *  are generally available (unless specifically marked as unsupported by a given converter)
     *
     *  @param   string   $sourceImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *
     *  @return  array  Array of options
     */
    public static function getPNGStandardOptions($sourceImageType)
    {
        $isPng = ($sourceImageType == 'png');

        $introMd = 'https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/' .
            'converting/introduction-for-converting.md';

        return OptionFactory::createOptions([
            ['compression', 'int', [
                'title' => 'Compression',
                'description' =>
                    'How much effort to use for compression (0-9).' .
                    'In case you enable "auto-limit", you can consider this property a maximum quality.',
                'default' => 7,
                'minimum' => 0,
                'maximum' => 9,
                'ui' => [
                    'component' => 'slider',
                    'advanced' => true,
                ]
            ]],
            ['interlace', 'boolean', [
                'title' => 'Interlace',
                'description' =>
                    'Beware than an interlaced PNG can be up to 7 times slower to write than a non-interlaced image.',
                'default' => false,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true,
                ]
            ]],
            ['dither', 'int', [
                'title' => 'Dither',
                'description' =>
                    'Amount of dithering (0-1).',
                'default' => 1,
                'minimum' => 0,
                'maximum' => 1,
                'ui' => [
                    'component' => 'slider',
                    'advanced' => true,
                ]
            ]],
        ]);
    }
}
