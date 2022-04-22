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
class GlobalStandardOptions
{

    /**
     *  Get the "general" options (options that are standard in the meaning that they
     *  are generally available (unless specifically marked as unsupported by a given converter)
     *
     *  @param   string   $sourceImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *
     *  @return  array  Array of options
     */
    public function getGlobalStandardOptions($sourceImageType)
    {
        return OptionFactory::createOptions([
            ['skip', 'boolean', ['default' => false]],
            ['log-call-arguments', 'boolean', ['default' => false]],

            // TODO: Should we keep these?
            ['jpeg', 'array', ['default' => []]],
            ['png', 'array', ['default' => []]],
        ]);
    }
}
