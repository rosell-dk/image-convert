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
    public function getAvifStandardOptions($sourceImageType)
    {
        $isPng = ($sourceImageType == 'png');

        $introMd = 'https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/' .
            'converting/introduction-for-converting.md';

        return OptionFactory::createOptions([
            ['quality', 'int', [
                'title' => 'Quality',
                'description' =>
                    'Q 30 gives about the same quality as JPEG Q 75.' .
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
            Gd uses "speed"
            Vips uses "effort", but used to use "speed".
            It seems they are related (but opposite)

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
