<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational;

use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;

class SystemRequirementsNotMetException extends ConverterNotOperationalException
{
    public $description = 'The converter is not operational (system requirements not met)';
}
