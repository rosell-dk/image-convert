<?php

namespace ImageConvert\Convert\Exceptions;

use ImageConvert\Exceptions\ImageConvertException;

/**
 *  ConversionFailedException is the base exception in the hierarchy for conversion errors.
 *
 *  Exception hierarchy from here:
 *
 *  WebpConvertException
 *      ConversionFailedException
 *          ConversionSkippedException
 *          ConverterNotOperationalException
 *              InvalidApiKeyException
 *              SystemRequirementsNotMetException
 *          FileSystemProblemsException
 *              CreateDestinationFileException
 *              CreateDestinationFolderException
 *          InvalidInputException
 *              ConverterNotFoundException
 *              InvalidImageTypeException
 *              InvalidOptionValueException
 *              TargetNotFoundException
 */
class ConversionFailedException extends ImageConvertException
{
    //public $description = 'Conversion failed';
    public $description = '';
}
