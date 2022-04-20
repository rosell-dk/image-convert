<?php

namespace ImageConvert\Options;

use ImageConvert\Options\StringOption;
use ImageConvert\Options\Exceptions\InvalidOptionValueException;

/**
 * Abstract option class
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class SensitiveArrayOption extends ArrayOption
{

    public function check()
    {
        parent::check();
    }

    public function getValueForPrint()
    {
        if (count($this->getValue()) == 0) {
            return '(empty array)';
        } else {
            return '(array of ' . count($this->getValue()) . ' items)';
        }
        //return '*****';
    }

    public function getDefinition()
    {
        $obj = parent::getDefinition();
        $obj['sensitive'] = true;
        return $obj;
    }
}
