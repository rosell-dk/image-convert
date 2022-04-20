<?php

namespace ImageConvert\Tests\Convert\TestConverters;

use ImageConvert\Convert\Converters\AbstractConverter;

class SuccessGuaranteedConverter extends AbstractConverter {

    public function doActualConvert()
    {
        file_put_contents($this->destination, 'we-pretend-this-is-a-valid-webp!');
    }
}
