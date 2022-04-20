<?php

namespace ImageConvert\Options;

use ImageConvert\Options\Option;
use ImageConvert\Options\Exceptions\InvalidOptionValueException;

/**
 * Boolean option
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class BooleanOption extends Option
{

    protected $typeId = 'boolean';
    protected $schemaType = ['boolean'];

    public function check()
    {
        $this->checkType('boolean');
    }

    public function getValueForPrint()
    {
        return ($this->getValue() === true ? 'true' : 'false');
    }
}
