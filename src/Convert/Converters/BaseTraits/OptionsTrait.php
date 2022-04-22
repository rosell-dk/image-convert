<?php

namespace ImageConvert\Convert\Converters\BaseTraits;

use ImageConvert\Convert\Converters\Stack;
use ImageConvert\Convert\Exceptions\ConversionFailed\ConversionSkippedException;
use ImageConvert\Options\Exceptions\InvalidOptionValueException;
use ImageConvert\Options\Exceptions\InvalidOptionTypeException;

use ImageConvert\Options\GhostOption;
use ImageConvert\Options\Options;
use ImageConvert\Options\OptionFactory;

use ImageConvert\StandardOptions\GlobalStandardOptions;
use ImageConvert\StandardOptions\PNGStandardOptions;
use ImageConvert\StandardOptions\WebPStandardOptions;
use ImageConvert\StandardOptions\AvifStandardOptions;

/**
 * Trait for handling options
 *
 * This trait is currently only used in the AbstractConverter class. It has been extracted into a
 * trait in order to bundle the methods concerning options.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
trait OptionsTrait
{

    abstract public function log($msg, $style = '');
    abstract public function logLn($msg, $style = '');
    abstract protected function getMimeTypeOfSource();

    /** @var array  Provided conversion options (array of simple objects)*/
    public $providedOptions;

    /** @var array  Calculated conversion options (merge of default options and provided options)*/
    protected $options;

    /** @var Options  */
    protected $options2;


    public function getGeneralOptionsForType($sourceImageType, $destinationImageType)
    {
        switch ($destinationImageType) {
            case 'webp':
                return WebPStandardOptions::getWebPStandardOptions($sourceImageType);
            case 'png':
                return PNGStandardOptions::getPNGStandardOptions($sourceImageType);
            case 'avif':
                return AvifStandardOptions::getAvifStandardOptions($sourceImageType);
        }
        return [];
    }

    /**
     *  Get the "general" options (options that are standard in the meaning that they
     *  are generally available (unless specifically marked as unsupported by a given converter)
     *
     *  @param   string   $sourceImageType        Image type of source image. Ie "jpeg". This may influence defaults
     *  @param   string   $destinationImageType   Image type of destination image.
     *
     *  @return  array  Array of options
     */
    public function getGeneralOptions($sourceImageType, $destinationImageType)
    {
        return array_merge(
            GlobalStandardOptions::getGlobalStandardOptions($sourceImageType),
            self::getGeneralOptionsForType($sourceImageType, $destinationImageType)
        );
    }

    /**
     *  Get the unique options for a converter
     *
     *  @param   string   $imageType   (png | jpeg)   The image type - determines the defaults
     *
     *  @return  array  Array of options
     */
    public function getUniqueOptions($imageType)
    {
        return [];
    }

    /**
     *  Create options.
     *
     *  The options created here will be available to all converters.
     *  Individual converters may add options by overriding this method.
     *
     *  @param   string   $sourceImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *  @param   string   $destinationImageType   Image type of source image. Ie "jpeg". This may influence defaults
     *
     *  @return void
     */
    protected function createOptions($sourceImageType, $destinationImageType)
    {
        $this->options2 = new Options();
        $this->options2->addOptions(... $this->getGeneralOptions($sourceImageType, $destinationImageType));
        $this->options2->addOptions(... $this->getUniqueOptions($sourceImageType, $destinationImageType));
    }

    /**
     * Set "provided options" (options provided by the user when calling convert().
     *
     * This also calculates the protected options array, by merging in the default options, merging
     * jpeg and png options and merging prefixed options (such as 'vips-quality').
     * The resulting options array are set in the protected property $this->options and can be
     * retrieved using the public ::getOptions() function.
     *
     * @param   array $providedOptions (optional)
     * @return  void
     */
    public function setProvidedOptions($providedOptions = [])
    {
        $this->createOptions($this->sourceType, $this->destinationType);

        $this->providedOptions = $providedOptions;

        if (isset($this->providedOptions['png'])) {
            if ($this->getMimeTypeOfSource() == 'image/png') {
                $this->providedOptions = array_merge($this->providedOptions, $this->providedOptions['png']);
//                $this->logLn(print_r($this->providedOptions, true));
                unset($this->providedOptions['png']);
            }
        }

        if (isset($this->providedOptions['jpeg'])) {
            if ($this->getMimeTypeOfSource() == 'image/jpeg') {
                $this->providedOptions = array_merge($this->providedOptions, $this->providedOptions['jpeg']);
                unset($this->providedOptions['jpeg']);
            }
        }

        // merge down converter-prefixed options
        $converterId = self::getConverterId();
        $strLen = strlen($converterId);
        foreach ($this->providedOptions as $optionKey => $optionValue) {
            if (substr($optionKey, 0, $strLen + 1) == ($converterId . '-')) {
                $this->providedOptions[substr($optionKey, $strLen + 1)] = $optionValue;
                unset($this->providedOptions[$optionKey]);
            }
        }

        // Create options (Option objects)
        foreach ($this->providedOptions as $optionId => $optionValue) {
            $this->options2->setOrCreateOption($optionId, $optionValue);
        }
        //$this->logLn(print_r($this->options2->getOptions(), true));
//$this->logLn($this->options2->getOption('hello'));

        // Create flat associative array of options
        $this->options = $this->options2->getOptions();

        // -  Merge $defaultOptions into provided options
        //$this->options = array_merge($this->getDefaultOptions(), $this->providedOptions);

        //$this->logOptions();
    }

    /**
     * Get the resulting options after merging provided options with default options.
     *
     * Note that the defaults depends on the mime type of the source. For example, the default value for quality
     * is "auto" for jpegs, and 85 for pngs.
     *
     * @return array  An associative array of options: ['metadata' => 'none', ...]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Change an option specifically.
     *
     * This method is probably rarely neeeded. We are using it to change the "encoding" option temporarily
     * in the EncodingAutoTrait.
     *
     * @param  string  $id      Id of option (ie "metadata")
     * @param  mixed   $value   The new value.
     * @return void
     */
    protected function setOption($id, $value)
    {
        $this->options[$id] = $value;
        $this->options2->setOrCreateOption($id, $value);
    }

    /**
     *  Check options.
     *
     *  @throws InvalidOptionTypeException   if an option have wrong type
     *  @throws InvalidOptionValueException  if an option value is out of range
     *  @throws ConversionSkippedException   if 'skip' option is set to true
     *  @return void
     */
    protected function checkOptions()
    {
        $this->options2->check();

        if ($this->options['skip']) {
            if (($this->getMimeTypeOfSource() == 'image/png') && isset($this->options['png']['skip'])) {
                throw new ConversionSkippedException(
                    'skipped conversion (configured to do so for PNG)'
                );
            } else {
                throw new ConversionSkippedException(
                    'skipped conversion (configured to do so)'
                );
            }
        }
    }

    public function logOptions()
    {
        $this->logLn('');
        $this->logLn('Options:');
        $this->logLn('------------');

        $unsupported = $this->getUnsupportedDefaultOptions();
        $received = [];
        $implicitlySet = [];
        foreach ($this->options2->getOptionsMap() as $id => $option) {
            if (in_array($id, [
                'png', 'jpeg', '_skip_input_check', '_suppress_success_message', 'skip', 'log_call_arguments'
            ])) {
                continue;
            }
            if ($option->isValueExplicitlySet()) {
                $received[] = $option;
            } else {
                if (($option instanceof GhostOption) || in_array($id, $unsupported)) {
                    //$received[] = $option;
                } else {
                    if (!$option->isDeprecated()) {
                        $implicitlySet[] = $option;
                    }
                }
            }
        }

        if (count($received) > 0) {
            foreach ($received as $option) {
                $this->log('- ' . $option->getId() . ': ');
                if ($option instanceof GhostOption) {
                    $this->log('  (unknown to ' . $this->getConverterId() . ')', 'bold');
                    $this->logLn('');
                    continue;
                }
                $this->log($option->getValueForPrint());
                if ($option->isDeprecated()) {
                    $this->log(' (deprecated)', 'bold');
                }
                if (in_array($option->getId(), $unsupported)) {
                    if ($this instanceof Stack) {
                        //$this->log('  *(passed on)*');
                    } else {
                        $this->log(' (unsupported by ' . $this->getConverterId() . ')', 'bold');
                    }
                }
                $this->logLn('');
            }
            $this->logLn('');
            $this->logLn(
                'Note that these are the resulting options after merging down the "jpeg" and "png" options and any ' .
                'converter-prefixed options'
            );
        }

        if (count($implicitlySet) > 0) {
            $this->logLn('');
            $this->logLn('Defaults:');
            $this->logLn('------------');
            $this->logLn(
                'The following options was not set, so using the following defaults:'
            );
            foreach ($implicitlySet as $option) {
                $this->log('- ' . $option->getId() . ': ');
                $this->log($option->getValueForPrint());
                /*if ($option instanceof GhostOption) {
                    $this->log('  **(ghost)**');
                }*/
                $this->logLn('');
            }
        }
    }

    // to be overridden by converters
    protected function getUnsupportedDefaultOptions()
    {
        return [];
    }

    public function getUnsupportedGeneralOptions()
    {
        return $this->getUnsupportedDefaultOptions();
    }

    /**
      * Get unique option definitions.
      *
      * Gets definitions of the converters "unique" options (that is, those options that
      * are not general). It was added in order to give GUI's a way to automatically adjust
      * their setting screens.
      *
      * @param  bool  $filterOutOptionsWithoutUI  If options without UI defined should be filtered out
      * @param  string   $imageType   (png | jpeg)   The image type - determines the defaults
      *
      * @return array  Array of options definitions - ready to be json encoded, or whatever
      */
    public function getUniqueOptionDefinitions($filterOutOptionsWithoutUI = true, $imageType = 'jpeg')
    {
        $uniqueOptions = new Options();
        //$uniqueOptions->addOptions(... $this->getUniqueOptions($imageType));
        foreach ($this->getUniqueOptions($imageType) as $uoption) {
            $uoption->setId(self::getConverterId() . '-' . $uoption->getId());
            $uniqueOptions->addOption($uoption);
        }

        $optionDefinitions = $uniqueOptions->getDefinitions();
        if ($filterOutOptionsWithoutUI) {
            $optionDefinitions = array_filter($optionDefinitions, function ($value) {
                return !is_null($value['ui']);
            });
            $optionDefinitions = array_values($optionDefinitions); // re-index
        }
        return $optionDefinitions;
    }

    /**
     * Get general option definitions.
     *
     * Gets definitions of all general options (not just the ones supported by current converter)
     * For UI's, as a way to automatically adjust their setting screens.
     *
     * @param  bool  $filterOutOptionsWithoutUI  If options without UI defined should be filtered out
     * @param  string   $imageType   (png | jpeg)   The image type - determines the defaults
     *
     * @return  array  Array of options definitions - ready to be json encoded, or whatever
     */
    public function getGeneralOptionDefinitions($filterOutOptionsWithoutUI = true, $imageType = 'jpeg')
    {
        $generalOptions = new Options();
        $generalOptions->addOptions(... $this->getGeneralOptions($imageType));
        //$generalOptions->setUI($this->getUIForGeneralOptions($imageType));
        $optionDefinitions = $generalOptions->getDefinitions();
        if ($filterOutOptionsWithoutUI) {
            $optionDefinitions = array_filter($optionDefinitions, function ($value) {
                return !is_null($value['ui']);
            });
            $optionDefinitions = array_values($optionDefinitions); // re-index
        }
        return $optionDefinitions;
    }

    public function getSupportedGeneralOptions($imageType = 'png')
    {
        $unsupportedGeneral = $this->getUnsupportedDefaultOptions();
        $generalOptionsArr = $this->getGeneralOptions($imageType);
        $supportedIds = [];
        foreach ($generalOptionsArr as $i => $option) {
            if (in_array($option->getId(), $unsupportedGeneral)) {
                unset($generalOptionsArr[$i]);
            }
        }
        return $generalOptionsArr;
    }

       /**
        *  Get general option definitions.
        *
        *  Gets definitions of the converters "general" options. (that is, those options that
        *  It was added in order to give GUI's a way to automatically adjust their setting screens.
        *
        *  @param   string   $imageType   (png | jpeg)   The image type - determines the defaults
        *
        *  @return  array  Array of options definitions - ready to be json encoded, or whatever
        */
    public function getSupportedGeneralOptionDefinitions($imageType = 'png')
    {
        $generalOptions = new Options();
        $generalOptions->addOptions(... $this->getSupportedGeneralOptions($imageType));
        return $generalOptions->getDefinitions();
    }

    public function getSupportedGeneralOptionIds()
    {
        $supportedGeneralOptions = $this->getSupportedGeneralOptions();
        $supportedGeneralIds = [];
        foreach ($supportedGeneralOptions as $option) {
            $supportedGeneralIds[] = $option->getId();
        }
        return $supportedGeneralIds;
    }
}
