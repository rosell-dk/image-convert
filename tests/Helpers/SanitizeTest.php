<?php

namespace ImageConvert\Tests\Helpers;

use ImageConvert\Helpers\Sanitize;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass ImageConvert\Helpers\Sanitize
 * @covers ImageConvert\Helpers\Sanitize
 */
class SanitizeTest extends TestCase
{

    /**
     * @covers ::removeNUL
     */
    public function testRemoveNUL()
    {
        $this->assertEquals(
            'a',
            Sanitize::removeNUL("a\0")
        );
    }

    /**
     * @covers ::removeStreamWrappers
     */
    public function testRemoveStreamWrappers()
    {
        $this->assertEquals(
            'dytdyt',
            Sanitize::removeStreamWrappers("phar://dytdyt")
        );
    }

}
