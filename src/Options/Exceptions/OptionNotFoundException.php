<?php

namespace ImageConvert\Options\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

class OptionNotFoundException extends ImageConvertException
{
    public $description = '';
}
