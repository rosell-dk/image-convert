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
class WebPStandardOptions
{

    /**
     *  Get the "general" options (options that are standard in the meaning that they
     *  are generally available (unless specifically marked as unsupported by a given converter)
     *
     *  @param   string   $sourceImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *
     *  @return  array  Array of options
     */
    public function getWebPStandardOptions($sourceImageType)
    {
        $isPng = ($sourceImageType == 'png');

        $introMd = 'https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/' .
            'converting/introduction-for-converting.md';

        return OptionFactory::createOptions([
            ['encoding', 'string', [
                'title' => 'Encoding',
                'description' => 'Set encoding for the webp. ' .
                    'If you choose "auto", webp-convert will ' .
                    'convert to both lossy and lossless and pick the smallest result',
                'default' => 'auto',
                'enum' => ['auto', 'lossy', 'lossless'],
                'ui' => [
                    'component' => 'select',
                    'links' => [['Guide', $introMd . '#auto-selecting-between-losslesslossy-encoding']],
                ]
            ]],
            ['quality', 'int', [
                'title' => 'Quality (Lossy)',
                'description' =>
                    'Quality for lossy encoding. ' .
                    'In case you enable "auto-limit", you can consider this property a maximum quality.',
                'default' => ($isPng ? 85 : 75),
                'default-png' => 85,
                'default-jpeg' => 75,
                //'minimum' => 0,
                //'maximum' => 100,
                "oneOf" => [
                    ["type" => "number", "minimum" => 0, 'maximum' => 100],
                    ["type" => "string", "enum" => ["auto"]]
                ],
                'ui' => [
                    'component' => 'slider',
                    'display' => "option('encoding') != 'lossless'"
                ]
            ]],
            ['auto-limit', 'boolean', [
                'title' => 'Auto-limit',
                'description' =>
                    'Enable this option to prevent an unnecessarily high quality setting for low ' .
                    'quality jpegs. It works by adjusting quality setting down to the quality of the jpeg. ' .
                    'Converting ie a jpeg with quality:50 to ie quality:80 does not get you better quality ' .
                    'than converting it to quality:80, but it does get you a much bigger file - so you ' .
                    'really should enable this option.' . "\n\n" .
                    'The option is ignored for PNG and never adjusts quality up. ' . "\n\n" .
                    'The feature requires Imagick, ImageMagick or Gmagick in order to detect the quality of ' .
                    'the jpeg. ' . "\n\n" .
                    'PS: The "auto-limit" option is relative new. However, before this option, you could achieve ' .
                    'the same by setting quality to "auto" and specifying a "max-quality" and a "default-quality". ' .
                    'These are deprecated now, but still works.',
                'default' => true,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true,
                    'links' => [
                        [
                            'Guide',
                            $introMd . '#preventing-unnecessarily-high-quality-setting-for-low-quality-jpegs'
                        ]
                    ],
                    'display' => "option('encoding') != 'lossless'"
                ]
            ]],
            ['alpha-quality', 'int', [
                'title' => 'Alpha quality',
                'description' =>
                    'Quality of alpha channel. ' .
                    'Often, there is no need for high quality transparency layer and in some cases you ' .
                    'can tweak this all the way down to 10 and save a lot in file size. The option only ' .
                    'has effect with lossy encoding, and of course only on images with transparency.',
                'default' => 85,
                'minimum' => 0,
                'maximum' => 100,
                'ui' => [
                    'component' => 'slider',
                    'links' => [['Guide', $introMd . '#alpha-quality']],
                    'display' => "(option('encoding') != 'lossless') && (imageType!='jpeg')"
                ]
            ]],
            ['near-lossless', 'int', [
                'title' => '"Near lossless" quality',
                'description' =>
                    'This option allows you to get impressively better compression for lossless encoding, with ' .
                    'minimal impact on visual quality. The range is 0 (maximum preprocessing) to 100 (no ' .
                    'preprocessing). Read the guide for more info.',
                'default' => 60,
                'minimum' => 0,
                'maximum' => 100,
                'ui' => [
                    'component' => 'slider',
                    'links' => [['Guide', $introMd . '#near-lossless']],
                    'display' => "option('encoding') != 'lossy'"
                ]
            ]],
            ['metadata', 'string', [
                'title' => 'Metadata',
                'description' =>
                    'Determines which metadata that should be copied over to the webp. ' .
                    'Setting it to "all" preserves all metadata, setting it to "none" strips all metadata. ' .
                    '*cwebp* can take a comma-separated list of which kinds of metadata that should be copied ' .
                    '(ie "exif,icc"). *gd* will always remove all metadata and *ffmpeg* will always keep all ' .
                    'metadata. The rest can either strip all or keep all (they will keep all, unless the option ' .
                    'is set to *none*)',
                'default' => 'none',
                'ui' => [
                    'component' => 'multi-select',
                    'options' => ['all', 'none', 'exif', 'icc', 'xmp'],
                ]
                // TODO: set regex validation
            ]],
            ['method', 'int', [
                'title' => 'Reduction effort (0-6)',
                'description' =>
                    'Controls the trade off between encoding speed and the compressed file size and quality. ' .
                    'Possible values range from 0 to 6. 0 is fastest. 6 results in best quality and compression. ' .
                    'PS: The option corresponds to the "method" option in libwebp',
                'default' => 6,
                'minimum' => 0,
                'maximum' => 6,
                'ui' => [
                  'component' => 'slider',
                  'advanced' => true,
                ]
            ]],
            ['sharp-yuv', 'boolean', [
                'title' => 'Sharp YUV',
                'description' =>
                    'Better RGB->YUV color conversion (sharper and more accurate) at the expense of a little extra ' .
                    'conversion time.',
                'default' => true,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true,
                    'links' => [
                        ['Ctrl.blog', 'https://www.ctrl.blog/entry/webp-sharp-yuv.html'],
                    ],
                ]
            ]],
            ['auto-filter', 'boolean', [
                'title' => 'Auto-filter',
                'description' =>
                    'Turns auto-filter on. ' .
                    'This algorithm will spend additional time optimizing the filtering strength to reach a well-' .
                    'balanced quality. Unfortunately, it is extremely expensive in terms of computation. It takes ' .
                    'about 5-10 times longer to do a conversion. A 1MB picture which perhaps typically takes about ' .
                    '2 seconds to convert, will takes about 15 seconds to convert with auto-filter. ',
                'default' => false,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true,
                ]
            ]],
            ['low-memory', 'boolean', [
                'title' => 'Low memory',
                'description' =>
                    'Reduce memory usage of lossy encoding at the cost of ~30% longer encoding time and marginally ' .
                    'larger output size. Only effective when the *method* option is 3 or more. Read more in ' .
                    '[the docs](https://developers.google.com/speed/webp/docs/cwebp)',
                'default' => false,
                'ui' => [
                    'component' => 'checkbox',
                    'advanced' => true,
                    'display' => "(option('encoding') != 'lossless') && (option('method')>2)"
                ]
            ]],
            ['preset', 'string', [
                'title' => 'Preset',
                'description' =>
                    'Using a preset will set many of the other options to suit a particular type of ' .
                    'source material. It even overrides them. It does however not override the quality option. ' .
                    '"none" means that no preset will be set',
                'default' => 'none',
                'enum' => ['none', 'default', 'photo', 'picture', 'drawing', 'icon', 'text'],
                'ui' => [
                    'component' => 'select',
                    'advanced' => true,
                ]
            ]],
            ['size-in-percentage', 'int', ['default' => null, 'minimum' => 0, 'maximum' => 100, 'allow-null' => true]],
            ['skip', 'boolean', ['default' => false]],
            ['log-call-arguments', 'boolean', ['default' => false]],
            // TODO: use-nice should not be a "general" option
            //['use-nice', 'boolean', ['default' => false]],
            ['jpeg', 'array', ['default' => []]],
            ['png', 'array', ['default' => []]],

            // Deprecated options
            ['default-quality', 'int', [
                'default' => ($isPng ? 85 : 75),
                'minimum' => 0,
                'maximum' => 100,
                'deprecated' => true]
            ],
            ['max-quality', 'int', ['default' => 85, 'minimum' => 0, 'maximum' => 100, 'deprecated' => true]],
        ]);
    }
}
