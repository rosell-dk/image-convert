<?php

namespace ImageConvert\Tests\Convert\TestConverters;

use ImageConvert\Convert\Converters\AbstractConverter;
use ImageConvert\Convert\Exceptions\ConversionFailedException;

class FailureGuaranteedConverter extends AbstractConverter {

    public function doActualConvert()
    {
        throw new ConversionFailedException('Failure guaranteed!');
    }
}
