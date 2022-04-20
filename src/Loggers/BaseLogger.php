<?php

namespace ImageConvert\Loggers;

/**
 * Base for all logger classes.
 *
 * ImageConvert can provide insights into the conversion process by means of accepting a logger which
 * extends this class.
 *
 * @package    ImageConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
abstract class BaseLogger
{
    /**
     * Write a message to the log
     *
     * @param  string  $msg     message to log
     * @param  string  $style   style (null | bold | italic)
     * @return void
     */
    abstract public function log($msg, $style = '');

    /**
     * Add new line to the log
     * @return void
     */
    abstract public function ln();

    /**
     * Write a line to the log
     *
     * @param  string  $msg     message to log
     * @param  string  $style   style (null | bold | italic)
     * @return void
     */
    public function logLn($msg, $style = '')
    {
        $this->log($msg, $style);
        $this->ln();
    }
}
