<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 18:34
 */

namespace myConf\Models;

/**
 * Class Config
 * @package myConf\Models
 * @author _g63<522975334@qq.com>
 * @version 2019.1
 */
class Config extends \myConf\BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key) : string
    {
        $r = $this->tables()->Configs->get($key);
        return !empty($r) ? $r['v'] : '';
    }

    /**
     * @param string $key
     * @param string $value
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set(string $key, string $value) : void
    {
        $this->tables()->Configs->set($key, $value);
    }
}