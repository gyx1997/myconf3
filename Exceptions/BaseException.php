<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 14:36
 */

namespace myConf\Exceptions;


class BaseException extends \Exception
{
    /**
     * @var string 用于直接在控制器上使用Json返回的短小错误码。
     */
    private $_short_message = '';

    public function __construct(string $short_message = 'UNKNOWN', string $message = 'Unknown Base Exception', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->_short_message = $short_message;
    }

    /**
     * 得到当前异常的短小错误码字符串
     * @return string 返回当前异常的短小错误码字符串
     */
    public function getShortMessage(): string
    {
        return $this->_short_message;
    }
}