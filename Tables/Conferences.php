<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 22:57
     */

    namespace myConf\Tables;

    use \myConf\Utils\DB;

    class Conferences extends \myConf\BaseSingleKeyTable {
        /**
         * @var array 会议状态
         */
        public static $conference_status = [
            'moderated' => 0,       //正在被审核
            'normal' => 1,          //正常显示
        ];

        public function __construct() {
            parent::__construct();
        }

        /**
         * 返回当前表的主键
         * @return string
         */
        public function primaryKey() : string {
            return 'conference_id';
        }

        /**
         * @return string
         */
        protected function actualPrimaryKey() : string {
            return 'conference_id';
        }

        /**
         * 返回当前表名
         * @return string
         */
        public function tableName() : string {
            return DB::MakeTable('conferences');
        }

        /**
         * 根据URL从数据库中取conference的信息
         * @param string $url
         * @return array
         */
        public function get_by_url(string $url) : array {
            return DB::FetchFirst($this->tableName(), ['conference_url' => $url]);
        }

        /**
         * 判断指定的URL的会议是否存在
         * @param string $url
         * @return bool
         */
        public function exist_by_url(string $url) : bool {
            return $this->existUsingWhere(array('conference_url' => $url));
        }
    }