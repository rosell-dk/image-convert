<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed;

use ImageConvert\Convert\Exceptions\ConversionFailedException;

class InvalidInputException extends ConversionFailedException
{
    public $description = 'Invalid input';
}
