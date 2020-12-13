<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 17:05
 */

namespace myConf;

/**
 * Class BaseService
 * @package myConf
 * @author _g63 <522975334@qq.com>
 * @version 2019.1
 * @property-read \myConf\Models Models
 */
class BaseService
{
    /**
     * @var Models 模型管理器
     */
    private $_models;
    
    private $models;

    /**
     * BaseService constructor.
     */
    public function __construct()
    {
        $models = Models::instance();
        $this->_models = &$models;
    }

    /**
     * @var array Sub services of the current service.
     */
    protected $subServices = array();

    /**
     * 魔术方法，获取服务的模型
     * @param $key
     * @return \myConf\Models|null
     */
    public function __get($key) {
        if ($key === 'Models') {
            return $this->_models;
        }
        return null;
    }
    
    /**
     * @return Models
     */
    protected function models()
    {
        return $this->_models;
    }

    /**
     * Get the result array of a service.
     * @param int $httpCode
     * @param string $statusMessage
     * @param array $data
     * @return array
     */
    public function getResultArray(int $httpCode, string $statusMessage, array $data = []) {
        return ['code' => $httpCode, 'status' => $statusMessage, 'data' => $data];
    }
}

