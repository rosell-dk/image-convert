<?php

namespace ImageConvert\Exceptions\InvalidInput;

use ImageConvert\Exceptions\InvalidInputException;

class InvalidImageTypeException extends InvalidInputException
{
    public $description = 'The converter does not handle the supplied image type';
}
