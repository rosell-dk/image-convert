<?php

namespace ImageConvert\Serve\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

class ServeFailedException extends ImageConvertException
{
    public $description = 'Failed serving';
}
