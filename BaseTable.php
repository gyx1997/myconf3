<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/23
     * Time: 10:51
     */

    namespace myConf;

    use \myConf\Utils\DB;

    /**
     * Class BaseTable 所有表的基类
     * @package myConf
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     * @property-read \myConf\Cache $Cache
     */
    abstract class BaseTable {

        /**
         * @var \myConf\Cache 缓存对象
         */
        private $_cache_object;

        /**
         * @var bool 是否使用基于主键的缓存
         */
        protected $_use_pk_cache = true;

        /**
         * BaseTable constructor.
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function __construct() {
            //根据实际表名初始化缓存驱动器
            $full_class = get_called_class();
            $classes = explode('\\', $full_class);
            $this->isReallyTable($full_class) && $this->_cache_object = new Cache(str_replace('\\', '-', end($classes)), 'dummy');
        }

        /**
         * 魔术方法，读取缓存驱动器
         * @param $key
         * @return \myConf\Cache
         */
        public function __get($key) {
            return $this->_cache_object;
        }

        /**
         * 主键缓存是否启用
         * @return bool
         */
        public function pk_cache_enabled() : bool {
            return $this->_use_pk_cache;
        }

        /**
         * 得到当前表的(逻辑)主键。
         * @return mixed
         */
        public abstract function primaryKey();

        /**
         * 得到当前表的（实际）主键
         * @return string
         */
        protected abstract function actualPrimaryKey() : string;

        /**
         * 通过逻辑主键值得到实际主键值，UPDATE和DELETE要用
         * @param $pk_val
         * @return int
         */
        protected abstract function _actual_pk_val($pk_val) : int;

        /**
         * 返回主键缓存的键名。
         * @param $val
         * @return string
         */
        public abstract function pk_cache_name($val) : string;

        /**
         * 删除指定主键的缓存
         * @param mixed $pk_val
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function pk_cache_delete($pk_val) : void {
            //如果这张表启用了主键缓存，删掉它（因为记录不存在了）
            $this->pk_cache_enabled() && $this->Cache->delete($this->pk_cache_name($pk_val));
        }

        /**
         * 当前表名
         * @return string
         */
        public abstract function tableName() : string;

        /**
         * 根据主键读取数据
         * @param mixed $pk_val
         * @param bool $from_db
         * @return array
         */
        public abstract function get($pk_val, bool $from_db = false) : array;

        /**
         * 判断当前主键的记录是否存在
         * @param mixed $pk_val
         * @return bool
         */
        public abstract function exist($pk_val) : bool;

        /**
         * 根据主键记录更新数据
         * @param mixed $pk_val
         * @param array $data
         */
        public abstract function set($pk_val, array $data = array()) : void;

        /**
         * 插入一条数据
         * @param array $data
         * @return int 返回当前的自增id值.
         */
        public abstract function insert(array $data = array()) : int;

        /**
         * 根据指定的主键值删除数据
         * @param string $pk_val
         */
        public abstract function delete($pk_val) : void;

        /**
         * 根据指定的Where条件组合判断数据表的记录是否存在
         * @param array $where_segment_array
         * @return bool 返回这样的记录是否存在
         */
        public function existUsingWhere(array $where_segment_array) : bool
        {
            return DB::Exist($this->tableName(), $where_segment_array);
        }

        /**
         * 得到本表的满足条件的第一条记录
         * @param array $where_segment
         * @param string $order_field
         * @param string $order_direction
         * @return array
         */
        public function fetchFirst(array $where_segment, string $order_field = '', string $order_direction = '') : array {
            return DB::FetchFirst($this->tableName(), $where_segment, $order_field, $order_direction);
        }

        /**
         * 得到本表的满足条件的所有记录
         * @param array $where_segment
         * @param string $order_field
         * @param string $order_direction
         * @param int $start
         * @param int $limit
         * @return array
         */
        public function fetchAll(array $where_segment = array(), string $order_field = '', string $order_direction = '', int $start = 0, int $limit = 0) : array {
            return DB::FetchAll($this->tableName(), $where_segment, $order_field, $order_direction, $start, $limit);
        }

        /**
         * @param array $data_set
         */
        public function insertArray(array $data_set) : void {
            DB::InsertArray($this->tableName(), $data_set);
        }

        /**
         * @param $sqlCommand
         * @param $parameters
         */
        public function sqlFetchAll($sqlCommand, $parameters) {
            return DB::FetchAllRaw($sqlCommand, $parameters);
        }

        /**
         * @param $sqlCommand
         * @param $parameters
         * @return array
         */
        public function sqlFetchFirst($sqlCommand, $parameters)
        {
            return DB::FetchFirstRaw($sqlCommand, $parameters);
        }

        /**
         * @param $whereSegment
         * @return int
         */
        public function count($whereSegment)
        {
            return DB::Count($this->tableName(), $whereSegment);
        }

        /**
         * 是否是实际的表（不是基类）
         * @param string $class
         * @return bool
         */
        private function isReallyTable(string $class) : bool {
            return $class !== 'myConf\BaseTable' && $class !== 'myConf\BaseSingleKeyTable' && $class !== 'myConf\BaseMultiRelationTable';
        }
    }