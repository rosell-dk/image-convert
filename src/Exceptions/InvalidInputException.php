<?php

namespace ImageConvert\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

class InvalidInputException extends ImageConvertException
{
    public $description = 'Invalid input';
}
