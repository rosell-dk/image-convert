<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational;

use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;

class InvalidApiKeyException extends ConverterNotOperationalException
{
    public $description = 'The converter is not operational (access denied)';
}
