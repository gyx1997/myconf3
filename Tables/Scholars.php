<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/15
     * Time: 17:32
     */

    namespace myConf\Tables;

    use \myConf\Utils\DB;

    class Scholars extends \myConf\BaseSingleKeyTable {

        public function __construct() {
            parent::__construct();
        }

        /**
         * 返回当前的主键
         * @return string
         */
        public function primaryKey() : string {
            return 'scholar_email';
        }

        /**
         * @return string
         */
        protected function actualPrimaryKey() : string {
            return 'scholar_id';
        }

        /**
         * 返回当前的表名
         * @return string
         */
        public function tableName() : string {
            return DB::MakeTable('scholars');
        }
    }