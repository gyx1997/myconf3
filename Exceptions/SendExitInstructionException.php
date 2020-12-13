<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/19
 * Time: 20:42
 */

namespace myConf\Exceptions;

class SendExitInstructionException extends BaseException
{
    public const DO_NOTHING = 0;
    public const DO_OUTPUT_JSON = 1;
    public const DO_OUTPUT_HTML = 2;
    private $_action;
    private $_data;

    /**
     * SendExitInstructionException constructor.
     * @param int $action
     * @param array $data
     * @param string $short_message
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(int $action = self::DO_NOTHING, $data = array(), string $short_message = 'UNKNOWN', string $message = 'Unknown Base Exception', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($short_message, $message, $code, $previous);
        $this->_action = $action;
        $this->_data = $data;
    }

    /**
     * @return int
     */
    public function getAction(): int
    {
        return $this->_action;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->_data;
    }
}