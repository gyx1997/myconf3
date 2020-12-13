<?php
/**
 * Created by PhpStorm.
 * User: 52297
 * Date: 2018/12/15
 * Time: 14:21
 */

namespace myConf;

use myConf\Exceptions\DbCompositeKeysException;
use \myConf\Utils\DB;

/**
 * Class BaseSingleKeyTable 所有单主键表的基类
 * @package myConf
 * @author _g63 <522975334@qq.com>
 * @version 2019.1
 * @property-read \myConf\Cache Cache
 */
abstract class BaseSingleKeyTable extends BaseTable {

    /**
     * BaseTable constructor.
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 得到当前表的主键名
     * dummy for base class, should be override in derived classes.
     * @return string
     */
    public abstract function primaryKey();

    /**
     * 得到当前表的（实际）主键
     * @return string
     */
    protected abstract function actualPrimaryKey() : string;

    /**
     * 得到当前表的包含前缀的表名
     * dummy for base class, should be override in derived classes.
     * @return string
     */
    public abstract function tableName() : string;

    /**
     * @param string $pk_val
     * @param bool $from_db
     * @return array
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function get($pk_val, bool $from_db = false) : array {
        $data = array();
        //如果没有启用主键缓存，那么无论如何都是从数据库读取的。
        $from_db = !$this->pk_cache_enabled() || $from_db;
        $cache_key = $this->pk_cache_name($pk_val);
        if ($from_db === false) {
            try {
                $data = $this->Cache->get($cache_key);
            } catch (\myConf\Exceptions\CacheMissException $e) {
                $from_db = true;
            }
        }
        if ($from_db === true) {
            $data = DB::FetchFirstRaw('SELECT * FROM ' . $this->tableName() . ' WHERE ' . $this->primaryKey() . '=\'' . $pk_val . '\' LIMIT 1');
            //如果这张表启用了基于主键的缓存，那么将其写入缓存。
            $this->pk_cache_enabled() && $this->Cache->set($cache_key, $data);
        }
        return $data;
    }

    /**
     * 判断当前主键的记录是否存在
     * @param string $pk_val
     * @return bool
     */
    public function exist($pk_val) : bool {
        return DB::Exist($this->tableName(), array($this->primaryKey() => $pk_val));
    }

    /**
     * 根据主键执行update操作
     * @param string $pk_val
     * @param array $data
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function set($pk_val, array $data = array()) : void {
        //对于实际主键id和逻辑主键id不一致的，先查询找出实际主键id
        DB::Update($this->tableName(), $data, [$this->actualPrimaryKey() => $this->_actual_pk_val($pk_val)]);
        $this->pk_cache_delete($pk_val);
    }

    /**
     * 插入一条数据
     * @param array $data
     * @return int 返回当前自增键的最新一条记录的PK id
     * @throws Exceptions\CacheDriverException
     * @throws Exceptions\DbException
     */
    public function insert(array $data = array()) : int {
        //主键唯一性约束检查
        //只要逻辑主键和物理主键不一致的，需要进行检查
        if ($this->actualPrimaryKey() !== $this->primaryKey()){
            //逻辑主键和物理主键不一致，但又不存在的，不满足完整性约束条件。
            if(!isset($data[$this->primaryKey()]) || $this->exist($data[$this->primaryKey()])) {
                throw new \myConf\Exceptions\DbException('DUPLICATE_PRIMARY_KEY', 'Duplicate Primary key detected.');
            }
        }
        DB::Insert($this->tableName(), $data);
        //删除掉旧数据，对于物理主键和逻辑主键不一致的情况，不能保证业务确定的逻辑主键前后完全不会重复
        $this->actualPrimaryKey() !== $this->primaryKey() && $this->Cache->delete($this->pk_cache_name($data[$this->primaryKey()]));
        return DB::LastInsertId();  //单主键表都应当是实体表，返回物理主键ID
    }

    /**
     * 根据主键删除一条记录
     * @param string $pk_val
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function delete($pk_val) : void {
        DB::Query('DELETE FROM `' . $this->tableName() . '` WHERE ' . $this->actualPrimaryKey() . '= \'' . $this->_actual_pk_val($pk_val) . '\'');
        $this->pk_cache_delete($pk_val);
    }

    /**
     * 对指定主键值确定的记录的某个字段做自增。
     * @param string $pk_val 主键键值
     * @param string $field 需要自增的字段
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function self_increase(string $pk_val, string $field) : void {
        DB::Query('UPDATE ' . $this->tableName() . " SET $field=$field+1 WHERE " . $this->actualPrimaryKey() . '=\'' . $this->_actual_pk_val($pk_val) . '\'');
        $this->pk_cache_delete($pk_val);
    }

    /**
     * 对指定主键值确定的记录的某个字段做自减。
     * @param string $pk_val 主键键值
     * @param string $field 需要自减的字段
     * @throws \myConf\Exceptions\CacheDriverException
     */
    public function self_decrease(string $pk_val, string $field) : void {
        DB::Query('UPDATE ' . $this->tableName() . " SET $field=$field-1 WHERE " . $this->actualPrimaryKey() . '=\'' . $this->_actual_pk_val($pk_val) . '\'');
        $this->pk_cache_delete($pk_val);
    }

    /**
     * 获取根据主键的缓存名
     * @param string $pk_val
     * @return string
     */
    public function pk_cache_name($pk_val) : string {
        return '<' . $this->primaryKey() . '>[' . $pk_val . ']';
    }

    /**
     * @param $pk_val
     * @return mixed
     */
    protected function _actual_pk_val($pk_val) : int {
        return ($this->primaryKey() !== $this->actualPrimaryKey()) ? DB::FetchFirstRaw('SELECT ' . $this->actualPrimaryKey() . ' FROM ' . $this->tableName() . ' WHERE ' . $this->primaryKey() . '=\'' . $pk_val . '\' LIMIT 1')[$this->actualPrimaryKey()] : $pk_val;
    }
}