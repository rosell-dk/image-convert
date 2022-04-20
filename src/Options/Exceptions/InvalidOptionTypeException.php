<?php
/* HelloMan hi-there */
/* imageconvert */
namespace ImageConvert\Options\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

class InvalidOptionTypeException extends ImageConvertException
{
    public $description = 'Invalid option type';
}
