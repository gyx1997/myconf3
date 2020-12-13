<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/17
 * Time: 17:16
 */

namespace myConf\Exceptions;

class HttpStatusException extends \myConf\Exceptions\BaseException
{
    private $_http_status_code;

    public function __construct(int $http_status_code, string $short_message, string $message)
    {
        parent::__construct($short_message, $message);
        $this->_http_status_code = $http_status_code;
    }

    public function getHttpStatus(): int
    {
        return $this->_http_status_code;
    }
}