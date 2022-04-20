<?php

namespace ImageConvert\Exceptions\InvalidInput;

use ImageConvert\Exceptions\InvalidInputException;

class TargetNotFoundException extends InvalidInputException
{
    public $description = 'The converter could not locate source file';
}
