<?php

namespace ImageConvert\Options\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

class InvalidOptionValueException extends ImageConvertException
{
    public $description = 'Invalid option value';
}
