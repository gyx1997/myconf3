<?php
    /**
     * Created by PhpStorm.
     * User: 52297
     * Date: 2018/12/21
     * Time: 16:36
     */

    namespace myConf;

    /**
     * 表管理器
     * Class Tables
     * @package myConf
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     * @property-read \myConf\Tables\Attachments $Attachments
     * @property-read \myConf\Tables\Categories $Categories
     * @property-read \myConf\Tables\ConferenceMembers $ConferenceMembers
     * @property-read \myConf\Tables\Conferences $Conferences
     * @property-read \myConf\Tables\Configs $Configs
     * @property-read \myConf\Tables\Documents $Documents
     * @property-read \myConf\Tables\Scholars $Scholars
     * @property-read \myConf\Tables\Users $Users
     * @property-read \myConf\Tables\Papers $Papers
     * @property-read \myConf\Tables\PaperAuthors $PaperAuthors
     * @property-read \myConf\Tables\PaperSessions $PaperSessions
     * @property-read \myConf\Tables\PaperReview $PaperReview
     */
    class Tables {
        /**
         * @var array 加载了的表的集合
         */
        private static $_tables = array();

        /**
         * For compatibility
         * @param string $table_name
         * @return BaseTable
         */
        public function __get(string $table_name)
        {
            return $this->$table_name();
        }
    
        /**
         * @param string $table_name
         *
         * @return mixed
         */
        public function __call(string $table_name, array $dummyArgs)
        {
            if (!array_key_exists($table_name, self::$_tables)
                || !isset(self::$_tables[$table_name])
                || is_null(self::$_tables[$table_name]))
            {
                $class_name = '\\myConf\\Tables\\' . $table_name;
                self::$_tables[$table_name] = new $class_name();
            }
            return self::$_tables[$table_name];
        }
    }