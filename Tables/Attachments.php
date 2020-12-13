<?php

    namespace myConf\Tables;

    use myConf\Utils\DB;

    /**
     * Class Attachments
     * @package myConf\Tables
     * @author _g63<522975334@qq.com>
     * @version 2019.1
     */
    class Attachments extends \myConf\BaseSingleKeyTable {

        public $tag_types = array('document' => 'document', 'paper' => 'paper', 'conf' => 'conf', '' => '');

        public const tag_type_conference = 'conf';
        public const tag_type_document = 'document';
        public const tag_type_paper = 'paper';
        public const tag_type_non_restrict = '';

        /**
         * Attachments constructor.
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function __construct() {
            parent::__construct();
        }

        /**
         * 返回当前表的主键
         * @return string
         */
        public function primaryKey() : string {
            return 'attachment_id';
        }

        /**
         * @return string
         */
        protected function actualPrimaryKey() : string {
            return 'attachment_id';
        }

        /**
         * 返回当前表名
         * @return string
         */
        public function tableName() : string {
            return DB::MakeTable('attachments');
        }

        /**
         * @param string $attachment_tag_type
         * @param int $attachment_tag_id
         * @return array
         */
        public function get_used(string $attachment_tag_type, int $attachment_tag_id) : array {
            return $this->fetchAll(array(
                    'attachment_tag_type' => $attachment_tag_type,
                    'attachment_tag_id' => $attachment_tag_id,
                    'attachment_used' => 1,
                ));
        }

        /**
         * 获取某一类的未使用的附件
         * @param string $tag_type
         * @param int $tag_id
         * @return array
         */
        public function get_unused(string $tag_type, int $tag_id) : array {
            return $this->fetchAll([
                    'attachment_tag_type' => $tag_type,
                    'attachment_tag_id' => $tag_id,
                    'attachment_used' => 0,
                ]);
        }

        /**
         * @param int $attachment_id
         * @param bool $used_status
         * @throws \myConf\Exceptions\CacheDriverException
         */
        public function SetUsedStatus(int $attachment_id, bool $used_status = true) : void {
            $this->set(strval($attachment_id), array('attachment_used' => ($used_status ? '1' : '0')));
        }

        /**
         * 重写父类的insert方法，自动对文件名进行CRC32处理
         * @param array $data
         * @return int
         */
        public function insert(array $data = []) : int {
            //CRC32处理文件名
            $data['attachment_filename_hash'] = crc32($data['attachment_file_name']);
            return parent::insert($data);
        }
    }