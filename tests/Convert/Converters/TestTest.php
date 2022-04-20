<?php
namespace ImageConvert\Tests\Convert\Converters;
use ImageConvert\Tests\Convert\Exposers\GdExposer;
use ImageConvert\Convert\Converters\Gd;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use ImageConvert\Convert\Exceptions\ConversionFailedException;

use PHPUnit\Framework\TestCase;

class TestTest extends TestCase
{

    public function testTesting()
    {
        $this->assertEquals(
            '1',
            '1'
        );
    }

}

require_once('pretend.inc');
