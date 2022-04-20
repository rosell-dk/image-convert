<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed;

use ImageConvert\Convert\Exceptions\ConversionFailedException;

class ConverterNotOperationalException extends ConversionFailedException
{
    public $description = 'The converter is not operational';
}
