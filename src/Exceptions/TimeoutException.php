<?php


namespace Cxlblm\Pool\Exception;


class TimeoutException extends \RuntimeException
{
    protected $message = 'timeout';
}