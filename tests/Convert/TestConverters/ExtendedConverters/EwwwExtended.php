<?php

namespace ImageConvert\Tests\Convert\TestConverters\ExtendedConverters;

use ImageConvert\Convert\Converters\Ewww;

class EwwwExtended extends Ewww
{
    public function callDoActualConvert()
    {
        $this->doActualConvert();
    }

}
