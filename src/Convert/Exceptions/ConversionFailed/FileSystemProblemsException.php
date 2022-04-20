<?php

namespace ImageConvert\Convert\Exceptions\ConversionFailed;

use ImageConvert\Convert\Exceptions\ConversionFailedException;

class FileSystemProblemsException extends ConversionFailedException
{
    public $description = 'Filesystem problems';
}
