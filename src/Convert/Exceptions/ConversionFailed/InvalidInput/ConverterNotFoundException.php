<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInput;

use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInputException;

class ConverterNotFoundException extends InvalidInputException
{
    public $description = 'The converter does not exist.';
}
