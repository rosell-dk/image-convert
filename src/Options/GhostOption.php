<?php

namespace ImageConvert\Options;

use ImageConvert\Options\Option;
use ImageConvert\Options\Exceptions\InvalidOptionValueException;

/**
 * Ghost option
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class GhostOption extends Option
{

    protected $typeId = 'ghost';

    public function getValueForPrint()
    {
        return '(not defined for this converter)';
    }
}
