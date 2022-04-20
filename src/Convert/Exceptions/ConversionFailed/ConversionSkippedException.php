<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed;

use ImageConvert\Convert\Exceptions\ConversionFailedException;

class ConversionSkippedException extends ConversionFailedException
{
    public $description = 'The converter declined converting';
}
