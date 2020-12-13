<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/18
 * Time: 11:24
 */

namespace myConf\Exceptions;


class UpdateConferenceException extends BaseException
{
    private $_error;

    public function __construct(array $error = array(), string $short_message = 'UNKNOWN', string $message = 'Unknown Base Exception', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($short_message, $message, $code, $previous);
        $this->_error = $error;
    }

    public function getErrorFlags(): array
    {
        return $this->_error;
    }
}