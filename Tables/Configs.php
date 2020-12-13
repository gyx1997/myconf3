<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 18:34
     */

    namespace myConf\Tables;

    use \myConf\Utils\DB;

    /**
     * Class Configs
     * @package myConf\Tables
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     */
    class Configs extends \myConf\BaseSingleKeyTable {
        /**
         * Configs constructor.
         */
        private $_config_data = array();

        public function __construct() {
            parent::__construct();
            $tmp = $this->fetchAll();
            foreach ($tmp as $t) {
                $this->_config_data[$t['k']] = $t;
            }
        }

        /**
         * 得到当前的主键名
         * @return string
         */
        public function primaryKey() : string {
            return 'k';
        }

        /**
         * @return string
         */
        protected function actualPrimaryKey() : string {
            return 'k';
        }

        /**
         * 得到当前表名
         * @return string
         */
        public function tableName() : string {
            return DB::MakeTable('configs');
        }

        /**
         * 重写父类方法，只从数据库读一次config，其余从临时变量读取
         * @param string $key
         * @param bool $from_db
         * @return array
         */
        public function get($key, bool $from_db = false) : array {
            return isset($this->_config_data[$key]) ? $this->_config_data[$key] : array();
        }

        /**
         * 重写父类方法，想config表写入值
         * @param string $key
         * @param array $data
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function set($key, array $data = array()) : void {
            parent::set($key, array('v' => $data['v']));
            $this->_config_data[$key] = $data['v'];
        }
    }