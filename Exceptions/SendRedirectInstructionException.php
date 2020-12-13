<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/16
 * Time: 14:48
 */

namespace myConf\Exceptions;


class SendRedirectInstructionException extends \myConf\Exceptions\BaseException
{
    private $redirect_to;

    /**
     * SendRedirectInstructionException constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct('REDIRECT_INSTRUCT', 'An URL redirect instruction has been sent.', 0, null);
        $this->redirect_to = $url;
    }

    /**
     * @return string
     */
    public function getRedirectURL(): string
    {
        return $this->redirect_to;
    }
}