<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 19:08
 */

namespace myConf;

/**
 * Class Session Session管理器
 * @package myConf
 * @property int $user_id 当前登陆用户ID，0为未登录
 * @property int $user_login_time 当前用户登录时间
 * @property string $captcha 当前使用的验证码
 */
class Session
{
    private $_ci_session;

    public function __construct()
    {
        $CI = &get_instance();
        $this->_ci_session = $CI->session;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exceptions\SessionKeyNotExistsException
     */
    public function __get(string $key)
    {
        if (!$this->_ci_session->has_userdata($key)) {
            throw new \myConf\Exceptions\SessionKeyNotExistsException('SESS_KEY_NOT_EXIST', 'Trying to visit key "' . $key . '" in session, but not found.');
        }
        return $this->_ci_session->$key;
    }

    /**
     * 设置session值 设置为null默认为删除。
     * @param string $key
     * @param $value
     */
    public function __set(string $key, $value): void
    {
        if ($value === null) {
            $this->_ci_session->unset_userdata($key);
        } else {
            $this->_ci_session->set_userdata($key, $value);
        }
    }

    /**
     * @param string $key
     * @param $value
     * @param int $ttl
     */
    public function set_with_ttl(string $key, $value, int $ttl): void
    {
        $this->_ci_session->set_tempdata($key, $value, $ttl);
    }

    /**
     * 一次性取回session值并销毁。
     * @param string $key
     * @return mixed
     * @throws Exceptions\SessionKeyNotExistsException
     */
    public function pull(string $key)
    {
        if (!$this->_ci_session->has_userdata($key)) {
            throw new \myConf\Exceptions\SessionKeyNotExistsException('SESS_KEY_NOT_EXIST', 'Trying to visit key "' . $key . '" in session, but not found.');
        }
        $data = $this->_ci_session->$key;
        $this->_ci_session->unset_tempdata($key);
        return $data;
    }

    /**
     * 销毁session
     */
    public function destory(): void
    {
        $this->_ci_session->sess_destroy();
    }
}