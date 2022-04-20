<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInput;

use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInputException;

class TargetNotFoundException extends InvalidInputException
{
    public $description = 'The converter could not locate source file';
}
