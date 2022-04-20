<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInput;

use ImageConvert\Convert\Exceptions\ConversionFailed\InvalidInputException;

class InvalidImageTypeException extends InvalidInputException
{
    public $description = 'The converter does not handle the supplied image type';
}
